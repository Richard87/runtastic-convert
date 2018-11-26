<?php

require __DIR__ . "/vendor/autoload.php";


use Symfony\Component\Console\Application;

$application = new Application();

$command = new \App\WorkoutCommand();
$application->add($command);

$application->run();

