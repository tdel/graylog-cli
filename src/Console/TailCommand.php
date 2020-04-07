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

class TailCommand extends Command
{
    protected static $defaultName = 'graylog:tail';

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
            ->setDescription('Tail content of a stream')
            ->setDefinition(
                new InputDefinition([
                    new InputArgument('stream', InputArgument::REQUIRED, 'Stream ID'),
                    new InputOption('search', 's', InputOption::VALUE_OPTIONAL),
                    new InputOption('follow', 'f', InputOption::VALUE_NONE),
                    new InputOption('lastsecs', 'l', InputOption::VALUE_OPTIONAL)
                ])
            );


    }

    /** @inheritDoc */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $isFollowMode = (bool) $input->getOption('follow');
        $streamId = $input->getArgument('stream');
        $query = $input->getOption('search');
        if (null === $query) {
            $query = ' ';
        }

        $lastSeconds = $input->getOption('lastsecs');
        if (null === $lastSeconds) {
            $lastSeconds = 1;
        }

        $result = $this->graylogClient->searchRelative($streamId, $query, $lastSeconds);
        if (false === empty($result['messages'])) {
            $output->writeln($this->formatter->format($result['messages']));
        }

        while ($isFollowMode) {
            $startTime = microtime(true);

            $result = $this->graylogClient->searchRelative($streamId, $query, 1);
            if (false === empty($result['messages'])) {
                $output->writeln($this->formatter->format($result['messages']));
            }

            $this->sleep($startTime);
        }

        return 0;
    }

    private function sleep(float $startTime): void
    {
        $stopTime = microtime(true);

        $sleep = (1 - ($stopTime - $startTime)) * 1000000;
        if ($sleep > 0) {
            usleep($sleep);
        }
    }
}

