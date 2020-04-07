<?php

namespace App\Console;

use App\HttpClient\GraylogClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StreamsCommand extends Command
{
    protected static $defaultName = 'graylog:streams';

    private $graylogClient;

    public function __construct(GraylogClient $graylogClient)
    {
        parent::__construct();

        $this->graylogClient = $graylogClient;
    }

    /** @inheritDoc */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = $this->graylogClient->fetchStreams();

        $rows = [];
        foreach ($result['streams'] as $element) {
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
