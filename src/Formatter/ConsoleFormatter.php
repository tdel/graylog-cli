<?php

namespace App\Formatter;

class ConsoleFormatter
{


    public function format(array $logLine): string
    {
        $rows = [];
        foreach ($logLine as &$element) {
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
                        $dt->setTimezone(new \DateTimeZone('Europe/Paris'));
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

        unset($element);

        $output = [];
        foreach ($rows as $row) {
            $output[] = $row['date']
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
        }

        return implode("\r\n", $output);
    }
}
