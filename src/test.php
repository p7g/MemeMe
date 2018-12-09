<?php
require_once 'vendor/autoload.php';

$flags = new \donatj\Flags();

while ($command = \readline('> ')) {
  $flags->parse(explode(' ', $command));
  foreach ($flags->args() as $thing) {
    var_dump($thing);
  }
}
