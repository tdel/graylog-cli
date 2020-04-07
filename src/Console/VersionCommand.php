<?php

namespace App\Console;

use App\HttpClient\GraylogClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpClient\HttpClient;

class VersionCommand extends Command
{
    protected static $defaultName = 'graylog:version';

    private $graylogClient;

    public function __construct(GraylogClient $graylogClient)
    {
        parent::__construct();

        $this->graylogClient = $graylogClient;
    }

    /** @inheritDoc */
    protected function configure(): void
    {
        // ...
    }

    /** @inheritDoc */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = $this->graylogClient->fetchVersion();

        foreach ($result as $key => $value) {
            $output->writeln($key . ' = ' . $value);
        }

        return 0;
    }
}
