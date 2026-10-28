<?php

use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}

// Rebuilds an empty schema in the test database (knowledge_learning_test) before the test suite.
// Each test then runs inside a transaction rolled back at the end (DAMA DoctrineTestBundle).
$kernel = new Kernel('test', false);
$kernel->boot();
$application = new Application($kernel);
$application->setAutoExit(false);
foreach ([
    ['command' => 'doctrine:database:create', '--if-not-exists' => true],
    ['command' => 'doctrine:schema:drop', '--force' => true, '--full-database' => true],
    ['command' => 'doctrine:schema:create'],
] as $command) {
    if (0 !== $application->run(new ArrayInput($command + ['--no-interaction' => true]), new NullOutput())) {
        throw new RuntimeException(sprintf('Unable to prepare the test database ("%s" failed).', $command['command']));
    }
}
$kernel->shutdown();
