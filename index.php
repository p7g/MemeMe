<?php
namespace p7g\MemeMe;

require_once __DIR__ . '/vendor/autoload.php';

use p7g\Discord\Client;
use p7g\Discord\Token\BotToken;

(new \Symfony\Component\Dotenv\Dotenv)->load('.env');

MemeMe::$logger = new \Monolog\Logger('MemeMe');
$sentry = new \Raven_Client(getenv('SENTRY_DSN'));
$handler = new \Monolog\Handler\RavenHandler($sentry, \Monolog\Logger::WARNING);
$handler->setFormatter(
  new \Monolog\Formatter\LineFormatter("%message% %context% %extra%\n")
);
MemeMe::$logger->pushHandler($handler);
$format = new \Monolog\Formatter\LineFormatter(null, 'Y-m-d H:i:s.u');
$handler = new \Monolog\Handler\StreamHandler(STDOUT);
$errHandler = new \Monolog\Handler\StreamHandler(
  STDERR,
  \Psr\Log\LogLevel::WARNING
);
$handler->setFormatter($format);
MemeMe::$logger->pushHandler($errHandler);
MemeMe::$logger->pushHandler($handler);

$error_handler = new \Raven_ErrorHandler($sentry);
$error_handler->registerExceptionHandler();
$error_handler->registerErrorHandler();
$error_handler->registerShutdownFunction();

$pdo = new \PDO('sqlite:data/db.sqlite3');
MemeMe::$db = new \LessQL\Database($pdo);
MemeMe::$db->setQueryCallback(function ($query, $params) {
  $paramString = \json_encode($params);
  MemeMe::$logger->debug("$query ($paramString)");
});

MemeMe::$token = new BotToken(getenv('TOKEN'));
MemeMe::$client = new Client(MemeMe::$token);
MemeMe::$client->setLogger(MemeMe::$logger);

bootstrap();
