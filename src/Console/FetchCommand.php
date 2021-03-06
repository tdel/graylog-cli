<?php

namespace App\Console;

use App\Formatter\ConsoleFormatter;
use App\HttpClient\GraylogClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FetchCommand extends Command
{
    protected static $defaultName = 'graylog:fetch';

    private $graylogClient;
    private $formatter;

    public function __construct(GraylogClient $graylogClient, ConsoleFormatter $formatter)
    {
        parent::__construct();

        $this->graylogClient = $graylogClient;
        $this->formatter = $formatter;
    }

    /** @inheritDoc */
    protected function configure(): void
    {
        $this
            ->setDescription('Fetch content of a stream')
            ->setDefinition(
                new InputDefinition([
                    new InputArgument('stream', InputArgument::REQUIRED, 'Stream ID'),
                    new InputOption('search', 's', InputOption::VALUE_OPTIONAL),
                    new InputOption('follow', 'f', InputOption::VALUE_NONE),
                    new InputOption('dateFrom', 'df', InputOption::VALUE_OPTIONAL),
                    new InputOption('dateTo', 'dt', InputOption::VALUE_OPTIONAL)
                ])
            );
    }

    /** @inheritDoc */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $streamId = $input->getArgument('stream');
        $query = $input->getOption('search');
        if (null === $query) {
            $query = ' ';
        }

        $result = $this->graylogClient->searchAbsolute(
            $streamId,
            $query,
            $input->getOption('dateFrom'),
            $input->getOption('dateTo')
        );

        if (false === empty($result['messages'])) {
            $output->writeln($this->formatter->format($result['messages']));
        }

        return 0;
    }
}
