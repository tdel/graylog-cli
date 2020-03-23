<?php

namespace App\Console;

use App\HttpClient\GraylogClientFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpClient\HttpClient;

class VersionCommand extends Command
{
    protected static $defaultName = 'graylog:version';

    private $clientFactory;

    public function __construct(GraylogClientFactory $clientFactory)
    {
        parent::__construct();

        $this->clientFactory = $clientFactory;
    }

    /** @inheritDoc */
    protected function configure(): void
    {
        // ...
    }

    /** @inheritDoc */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $client = $this->clientFactory->create();
        try {
            $response = $client->request('GET', '/api');
            $json = $response->getContent();
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());

            return 1;
        }

        $array = json_decode($json, true);

        foreach ($array as $key => $value) {
            $output->writeln($key . ' = ' . $value);
        }

        return 0;
    }
}
