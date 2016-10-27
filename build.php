#!/usr/bin/php
<?php

require_once('vendor/autoload.php');

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

$application = new Application();

class ValidateCommand extends Command
{
    protected function configure()
    {
        $this
		    // the name of the command (the part after "bin/console")
		    ->setName('app:validate')
		    // the short description shown while running "php bin/console list"
		    ->setDescription('Runs all unit tests and code sniffing');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Code sniffing...</info>');

        $exitCode = 0;

        passthru('vendor/bin/phpcs -n -p --standard=PSR2 --extensions=php src/ tests/', $exitCode);

        if ($exitCode) {
        	$output->writeln('<error>Code sniffing failed!</error>');
        	return;
        }

        $output->writeln('<info>Unit tests...</info>');

        $exitCode = 0;

		passthru('vendor/bin/phpunit -c phpunit.xml tests/', $exitCode);

        if ($exitCode) {
        	$output->writeln('<error>Unit tests failed!</error>');
        	return;
        }

        $output->writeln('<info>All good!</info>');

    }
}

class FixCommand extends Command
{
    protected function configure()
    {
        $this
		    // the name of the command (the part after "bin/console")
		    ->setName('app:fix')
		    // the short description shown while running "php bin/console list"
		    ->setDescription('Auto-fix the code sniffing failures if possible');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Fixing code formatting...</info>');

        $exitCode = 0;

        passthru('vendor/bin/phpcbf --standard=PSR2 --extensions=php src/ tests/', $exitCode);

        if ($exitCode) {
        	$output->writeln('<error>Something broke!</error>');
        	return;
        }

        $output->writeln('<info>All Fixed!</info>');

    }
}

// ... register commands
$application->add(new ValidateCommand);
$application->add(new FixCommand);

$application->run();