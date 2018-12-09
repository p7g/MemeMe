<?php
namespace p7g\MemeMe;

use GetOpt\GetOpt;
use GetOpt\Command;
use GetOpt\Option;
use GetOpt\Operand;

function defineCommands(GetOpt $getopt) {
  $getopt->addOptions([
    Option::create(null, 'version', GetOpt::NO_ARGUMENT)
      ->setDescription('Show version'),
    Option::create(null, 'help', GetOpt::NO_ARGUMENT)
    ->setDescription('Show help'),
  ]);

  $getopt->addCommands([
    getChannelCommand(),
    getPermissionsCommand(),
    getEvalCommand(),
    getGenCommand(),
  ]);
}

function getChannelCommand(): Command {
  $command = Command::create('channel', 'channel')
    ->setDescription('Enable or disable channels');

  $actionOperand = Operand::create(
    'action',
    Operand::REQUIRED
  )->setDescription('The action to do on the channels (enable or disable)');

  $channelIdOperand = Operand::create(
    'channelId',
    Operand::MULTIPLE | Operand::REQUIRED
  )->setDescription('Any channels you want to enable/disable');

  $command->addOperands([$actionOperand, $channelIdOperand]);

  return $command;
}

function getPermissionsCommand(): Command {
  $command = Command::create('permissions', 'permissions')
    ->setDescription('Set the permissions level of a user');

  $userOperand = Operand::create('user', Operand::REQUIRED)
    ->setDescription('A mention or user ID');

  $permissionOperand = Operand::create('permission', Operand::OPTIONAL)
    ->setDescription(
      'Can be one of admin, mod, user, or none. Omit to get current level'
    );

  $command->addOperands([$userOperand, $permissionOperand]);

  return $command;
}

function getEvalCommand(): Command {
  $command = Command::create('eval', 'eval')
    ->setDescription('Evaluate arbitrary code. You can\'t use this');

  $codeOperand = Operand::create('code', Operand::REQUIRED | OPERAND::MULTIPLE)
    ->setDescription('The code to evaluate');

  return $command;
}

function getGenCommand(): Command {
  $command = Command::create('gen', 'gen')
    ->setDescription('Generate a meme');

  $altOption = Option::create('a', 'alt', GetOpt::REQUIRED_ARGUMENT)
    ->setDescription('Specify an alternate style for the meme');

  $fontOption = Option::create('f', 'font', GetOpt::REQUIRED_ARGUMENT)
    ->setDescription('Use a different font');

  $widthOption = Option::create('w', 'width', GetOpt::REQUIRED_ARGUMENT)
    ->setDescription('Specify the width of the resulting image');

  $heightOption = Option::create('h', 'height', GetOpt::REQUIRED_ARGUMENT)
    ->setDescription('Specify the height of the resulting image');

  $command->addOptions([
    $altOption,
    $fontOption,
    $widthOption,
    $heightOption,
  ]);

  $typeOperand = Operand::create('type', Operand::REQUIRED)
    ->setDescription('The type of meme to generate');

  $firstTextOperand = Operand::create('firstText', Operand::REQUIRED)
    ->setDescription('The text that goes at the top');

  $secondTextOperand = Operand::create('secondText', Operand::REQUIRED)
    ->setDescription('The text that goes at the bottom');

  $command->addOperands([
    $typeOperand,
    $firstTextOperand,
    $secondTextOperand,
  ]);

  return $command;
}
