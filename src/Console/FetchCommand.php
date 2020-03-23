<?php

namespace App\Console;

use App\HttpClient\GraylogClientFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FetchCommand extends Command
{
    protected static $defaultName = 'graylog:fetch';

    private $clientFactory;

    public function __construct(GraylogClientFactory $clientFactory)
    {
        parent::__construct();

        $this->clientFactory = $clientFactory;
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
        $client = $this->clientFactory->create();

        $isFollowMode = (bool) $input->getOption('follow');
        $streamId = $input->getArgument('stream');
        $query = $input->getOption('search');
        if (null === $query) {
            $query = '';
        }

        $range = 1;

        $queryArgsArray = [
            'query' => $query,
            'sort' => 'desc',
            'filter' => 'streams:' . $streamId
        ];

        $dateFrom = $input->getOption('dateFrom');
        if (null !== $dateFrom) {
            $fromTime = new \DateTime($dateFrom);
        } else {
            $fromTime = new \DateTime('now');
            $fromTime->modify('- 2 seconds');
        }

        $dateTo = $input->getOption('dateTo');
        if (null !== $dateTo) {
            $toTime = new \DateTime($dateTo);
        } else {
            $toTime = new \DateTime('now');
            $toTime->modify('- 1 seconds');
        }

        $fromTime->setTime($fromTime->format('H'), $fromTime->format('i'), $fromTime->format('s'), 0);
        $toTime->setTime($toTime->format('H'), $toTime->format('i'), $toTime->format('s'), 0);

        do {
            $startTime = microtime(true);

            $clonedFromTime = clone $fromTime;
            $clonedFromTime->setTimezone(new \DateTimeZone("UTC"));

            $cloneToTime = clone $toTime;
            $cloneToTime->setTimezone(new \DateTimeZone("UTC"));

            $queryArgsArray['from'] = $clonedFromTime->format("Y-m-d\TH:i:s.000\Z");
            $queryArgsArray['to'] = $cloneToTime->format("Y-m-d\TH:i:s.000\Z");

            try {
                $response = $client->request('GET', '/api/search/universal/absolute', ['query' => $queryArgsArray]);
                $json = $response->getContent();
            } catch (\Exception $e) {
                $output->writeln($e->getMessage());

                return 1;
            }

            $array = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

            if (false === empty($array['messages'])) {
                $rows = [];
                foreach ($array['messages'] as &$element) {
                    $contextArgs = [];
                    $coreArgs = [];
                    $processorArgs = [];

                    foreach ($element['message'] as $key => $value) {
                        if (in_array($key, ['_id', 'streams'], true)) {
                            unset($element['message'][$key]);

                            continue;
                        }

                        if (false !== strpos($key, 'gl2_')) {
                            unset($element['message'][$key]);

                            continue;
                        }

                        if (false !== strpos($key, 'ctxt_')) {
                            $contextArgs[] = $key . ': ' . $value;

                            unset($element['message'][$key]);

                            continue;
                        }

                        switch ($key) {
                            case 'timestamp':
                                $dt = \DateTime::createFromFormat("Y-m-d\TH:i:s.u\Z", $element['message']['timestamp'], new \DateTimeZone("UTC"));
                                $dt->setTimezone(new \DateTimeZone("Europe/Paris"));
                                //$dt = new \DateTime($element['message']['timestamp']);
                                $coreArgs['date'] = $dt->format('Y-m-d H:i:s');
                                unset($element['message'][$key]);

                                continue 2;
                            case 'level':
                            case 'facility':
                            case 'message':
                                $coreArgs[$key] = $value;
                                unset($element['message'][$key]);

                                continue 2;
                        }

                        $processorArgs[] = $key . ': ' . $value;
                    }

                    sort($contextArgs);
                    sort($processorArgs);


                    $rows[] = [
                        'date' => $coreArgs['date'],
                        'level' => $coreArgs['level'],
                        'facility' => $coreArgs['facility'],
                        'message' => $coreArgs['message'],
                        'args' => implode(', ', $contextArgs),
                        'context' => implode(', ', $processorArgs),
                    ];

                    unset(
                        $element['message']['timestamp'],
                        $element['message']['facility'],
                        $element['message']['level'],
                        $element['message']['message']
                    );

                }

                foreach ($rows as $row) {

                    $string = $row['date']
                        . ' '
                        . $row['level']
                        . ' '
                        . $row['facility']
                        . "\t"
                        . '<fg=green>' . $row['message'] . '</>'
                        . ' '
                        . '[<fg=cyan>' . $row['args'] . '</>]'
                        . ' '
                        . '[<fg=magenta>' . $row['context'] . '</>]';

                    $output->writeln($string);
                }
            }

            $stopTime = microtime(true);

            $sleep = (1 - ($stopTime - $startTime)) * 1000000;
            usleep($sleep);

            $toTime->modify('+ 1 seconds');
            $fromTime->modify('+ 1 seconds');
        } while ($isFollowMode);

        return 0;
    }
}
