<?php

require_once "Command.php";

$command = $argv[1];
$extension = $argv[2] ?? null;

if (Command::getOS() != "Windows") {
    ConsoleOutput::error("The PECL 1.0.0 works only for Windows")->print()->exit();
}

$cmd = new Command($command, $extension);