<?php

namespace App\Console;

use App\HttpClient\GraylogClientFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StreamsCommand extends Command
{
    protected static $defaultName = 'graylog:streams';

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
            $response = $client->request('GET', '/api/streams');
            $json = $response->getContent();
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());

            return 1;
        }

        $array = json_decode($json, true);

        $rows = [];
        foreach ($array['streams'] as $element) {
            $rows[] = [
                $element['id'],
                $element['title'],
                $element['description']
            ];
        }

        $table = new Table($output);
        $table
            ->setHeaders(['ID', 'Name', 'Description'])
            ->setRows($rows)
        ;

        $table->render();

        return 0;
    }

}
