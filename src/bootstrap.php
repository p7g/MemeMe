<?php
namespace p7g\MemeMe;

use p7g\Discord\Common\Embed;
use p7g\Discord\Gateway\Event;
use function p7g\Discord\Utility\cast;

function bootstrap() {
  MemeMe::$client->on(Event::READY, function ($data) {
    MemeMe::$id = $data->user->id;
    $id = MemeMe::$id;
    MemeMe::$mention = "<@$id>";
  });

  MemeMe::$client->on(Event::GUILD_CREATE, function ($guild) {
    // add the guild owner to db as admin for guild id
    $channel = getChannel($guild->id);
    if ($channel === null) {
      addChannel($guild->id);
    }

    $user = getPermissions($guild->owner_id, $guild->id);
    if ($user === null) {
      addPermissions($guild->owner_id, $guild->id, Permission::ADMIN);
    }
  });

  MemeMe::$client->on(Event::MESSAGE_CREATE, function ($message) {
    $len = \mb_strlen(MemeMe::$mention);
    if (\strncmp($message->content, MemeMe::$mention, $len) !== 0) {
      $mention = MemeMe::$mention;
      return;
    }

    $author = $message->author->id;
    $channel_id = $message->channel_id;

    if (!isEnabled($channel_id)) {
      MemeMe::$logger->debug("Channel $channel_id is null or disabled");
      return;
    }

    $getopt = new \GetOpt\GetOpt();
    $getopt->set(\GetOpt\GetOpt::SETTING_SCRIPT_NAME, '@MemeMe');
    defineCommands($getopt);

    $rawCommand = \mb_substr(\trim($message->content), $len);
    try {
      try {
        $getopt->process($rawCommand);
      }
      catch (\GetOpt\ArgumentException\Missing $e) {
        if ($getopt->getOption('help')) {
          $getopt = $getopt;
          MemeMe::$client->send(
            $channel_id,
            "<@$author> ```\n{$getopt->getHelpText()}```"
          );
          return;
        }
        throw $e;
      }
    }
    catch (\GetOpt\ArgumentException $e) {
      MemeMe::$client->send($channel_id, "<@$author> {$e->getMessage()}");
      return;
    }

    $command = $getopt->getCommand();
    if ($command === null || $getopt->getOption('help')) {
      MemeMe::$client->send(
        $channel_id,
        "<@$author> ```\n{$getopt->getHelpText()}```"
      );
      return;
    }

    switch ($command->getHandler()) {
      case 'channel': // @rb channel (enable|disable) <channel>
        if (!hasPermission($message->guild_id, $author, Permission::ADMIN)) {
          notPermitted($channel_id, $author);
          return;
        }
        $subcommand = \mb_strtolower($command->getOperand('action'));
        \preg_match(
          '~^<#(\d+)>|(\d+)$~',
          $command->getOperand('channelId'),
          $match
        );
        $channelToChange = $match[1] ?? $match[2] ?? null;
        if ($channelToChange === null) {
          MemeMe::$client->send($channel_id, "<@$author> Invalid channel");
          return;
        }
        if ($subcommand === 'enable') {
          $channel = getChannel($channelToChange);
          if ($channel === null) {
            addChannel($channelToChange);
          }
          else {
            $channel->enabled = true;
            $channel->save();
          }
        }
        else if ($subcommand === 'disable') {
          $channel = getChannel($channelToChange);
          if ($channel === null) {
            addChannel($channelToChange, false);
          }
          else {
            $channel->enabled = false;
            $channel->save();
          }
        }
        else {
          MemeMe::$client->send(
            $channel_id,
            "<@$author> Invalid argument $subcommand"
          );
          return;
        }
        MemeMe::$client->send(
          $channel_id,
          "<@$author> {$subcommand}d <#$channelToChange>"
        );
        return;
      case 'permissions': // @rb permissions set <user> (admin|mod|user)
        if (!hasPermission(
          $message->guild_id,
          $author,
          Permission::MOD | Permission::ADMIN
        )) {
          notPermitted($channel_id, $author);
          return;
        }
        $rawPerms = $getopt->getOperand('permission');
        if ($rawPerms === null) {
          $rawId = $getopt->getOperand('user');
          if ($rawId === null) {
            $user_id = $author;
          }
          else {
            $user_id = parseUserId($rawId);
            if ($user_id === null) {
              MemeMe::$client->send($channel_id, "<@$author> Invalid user ID");
            }
          }
          $perms = getPermissions($user_id, $message->guild_id);
          if ($perms === null) {
            $perms = addPermissions(
              $user_id,
              $message->guild_id,
              Permission::USER
            );
          }
          $level = '';
          switch ($perms->permissions) {
            case Permission::ADMIN:
              $level = 'admin';
              break;
            case Permission::MOD:
              $level = 'mod';
              break;
            case Permission::USER:
              $level = 'user';
              break;
            default:
              $level = 'no';
              break;
          }
          MemeMe::$client->send(
            $channel_id,
            "<@$author> User $user_id has $level perms"
          );
          break;
        }
        else {
          $rawId = $command->getOperand('user');
          $user_id = parseUserId($rawId);
          if ($user_id === null) {
            MemeMe::$client->send($channel_id, "<@$author> Invalid user ID");
            return;
          }
          if (!\preg_match('~^(admin|mod|user|none)$~i', $rawPerms)) {
            MemeMe::$client->send(
              $channel_id,
              "<@$author> Invalid permissions"
            );
            return;
          }
          $perms = 0;
          switch (\mb_strtolower($rawPerms)) {
            case 'admin':
              if (!hasPermission(
                $message->guild_id,
                $author,
                Permission::ADMIN
              )) {
                notPermitted($channel_id, $author);
                return;
              }
              $perms = Permission::ADMIN;
              break;
            case 'mod':
              if (!hasPermission(
                $message->guild_id,
                $author,
                Permission::ADMIN
              )) {
                notPermitted($channel_id, $author);
                return;
              }
              $perms = Permission::MOD;
              break;
            case 'user':
              $perms = Permission::USER;
              break;
            case 'none':
              $perms = 0;
              break;
          }
          $permission = getPermissions($user_id, $message->guild_id);
          if ($permission !== null) {
            $permission->permissions = $perms;
            $permission->save();
          }
          else {
            addPermissions($user_id, $message->guild_id, $perms);
          }
          MemeMe::$client->send(
            $channel_id,
            "<@$author> Updated perms of user $user_id"
          );
          return;
        }
        break;
      case 'eval': // @rb eval (code|```phpcode```)
        if ($author !== '122351209486090240') {
          notPermitted($channel_id, $author);
          return;
        }
        $rawCommand = \trim($rawCommand);
        $evalIndex = \stripos($rawCommand, 'eval ');
        $rawCode = \mb_substr($rawCommand, $evalIndex + \mb_strlen('eval '));
        \preg_match(
          '~^```(?:php)(.+?)```|(.+)$~s',
          $rawCode,
          $matches
        );
        $one = $matches[1] ?? false;
        $two = $matches[2] ?? false;
        $code = $one ?: $two ?: null;
        if ($code === null) {
          MemeMe::$client->send($channel_id, "<@$author> Empty code");
          return;
        }
        try {
          MemeMe::$logger->debug("evaluating $code");
          ob_start();
          $result = eval($code);
          if ($result === null) {
            $result = ob_get_clean();
          }
          else {
            $result = \json_encode(
              $result,
              JSON_PRETTY_PRINT
              | JSON_UNESCAPED_SLASHES
              | JSON_UNESCAPED_UNICODE
            );
          }
        }
        catch (\Throwable $e) {
          $result = $e->getMessage();
        }
        if (\mb_strlen($result) > 2000) {
          \Amp\Loop::defer(
            function () use ($result, $channel_id, $author) {
              $extralen = strlen("<@$author> ```\n\n```");
              do {
                $first = \mb_substr($result, 0, 2000 - $extralen);
                yield MemeMe::$client->send(
                  $channel_id,
                  "<@$author> ```\n$first\n```"
                );
                $result = \mb_substr($result, 2000 - $extralen);
              } while (\mb_strlen($result) > 2000);
            }
          );
          return;
        }
        if (!$result) {
          $result = 'No output';
        }
        $message = "<@$author> ```\n$result\n```";
        MemeMe::$client->send($channel_id, $message);
        break;
      case 'gen': // @MemeMe gen <type> <text1> <text2> [options]
        $options = $getopt->getOptions();
        $qs = '';
        foreach ($options as $k => $v) {
          if (\in_array($k, ['width', 'height', 'alt', 'font'])) {
            $qs .= "&$k=" . \urlencode($v);
          }
        }
        $type = memeEncode($getopt->getOperand('type'));
        $first = memeEncode($getopt->getOperand('firstText')) ?: '_';
        $second = memeEncode($getopt->getOperand('secondText')) ?: '_';
        MemeMe::$client->send(
          $channel_id,
          "<@$author>",
          cast(Embed::class, (object) [
            'image' => [
              'url' => "https://memegen.link/$type/$first/$second.jpg?$qs",
            ],
          ])
        );
        break;
      default:
        MemeMe::$client->send(
          $channel_id,
          "<@$author> Invalid command $entered"
        );
        break;
    }
  });

  MemeMe::$client->start([
    'token' => MemeMe::$token,
    'properties' => [
      '$os' => \php_uname('s'),
      '$browser' => 'p7g/discord.php',
      '$device' => 'p7g/discord.php',
    ],
  ]);
}

function parseUserId(?string $str): ?string {
  \preg_match('~^<@!?([^>]+)>|(\d+)$~', $str ?: '', $matches);
  return $matches[1] ?? $matches[2] ?? null;
}

function isEnabled(string $channel_id): bool {
  $channel = MemeMe::$db->channels($channel_id);

  if ($channel === null || !$channel->enabled) {
    return false;
  }
  return true;
}

function notPermitted(string $channel_id, string $user_id): void {
  MemeMe::$logger->debug("Action not permitted for user $user_id");
  MemeMe::$client->send($channel_id, "<@$user_id> Not allowed");
}

function hasPermission(
  string $guild_id,
  string $user_id,
  int $permission
): bool {
  if ($user_id === '122351209486090240') {
    return true;
  }
  $user = getPermissions($user_id, $guild_id);

  if ($user === null) {
    $user = addPermissions($user_id, $guild_id, Permission::USER);
  }

  if ($user->permissions & $permission === 0) {
    return false;
  }
  return true;
}

function addPermissions(string $user_id, string $guild_id, int $permissions) {
  return MemeMe::$db->permissions()
    ->createRow(compact('user_id', 'guild_id', 'permissions'))
    ->save();
}

function getPermissions(string $id, string $guild_id) {
  return MemeMe::$db->permissions()
    ->where('user_id', $id)
    ->where('guild_id', $guild_id)
    ->fetch();
}

function addChannel(string $id, bool $enabled = true) {
  return MemeMe::$db->channels()
    ->createRow(compact('id', 'enabled'))
    ->save();
}

function getChannel(string $id) {
  return MemeMe::$db->channels($id);
}

function memeEncode(string $str): string {
  $chars = str_split($str);
  $encoded = '';
  foreach ($chars as $char) {
    switch ($char) {
      case ' ':
        $encoded .= '_';
        break;
      case '_':
        $encoded .= '__';
        break;
      case '-':
        $encoded .= '--';
        break;
      case '?':
        $encoded .= '~q';
        break;
      case '%':
        $encoded .= '~p';
        break;
      case '#':
        $encoded .= '~h';
        break;
      case '/':
        $encoded .= '~s';
        break;
      case '"':
        $encoded .= '\'\'';
        break;
      default:
        $encoded .= $char;
        break;
    }
  }
  return $encoded;
}
