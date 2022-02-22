<?php

namespace MargaTampu\LaravelTeamsLogging;

use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;

class LoggerHandler extends AbstractProcessingHandler
{
    /** @var string */
    private $url;

    /** @var string */
    private $style;

    /** @var string */
    private $name;

    /**
     * @param $url
     * @param int $level
     * @param string $name
     * @param bool $bubble
     */
    public function __construct($url, $level = MonologLogger::DEBUG, $style = 'simple', $name = 'Default', $bubble = true)
    {
        parent::__construct($level, $bubble);

        $this->url   = $url;
        $this->style = $style;
        $this->name  = $name;
    }

    /**
     * @param array $record
     *
     * @return LoggerMessage
     */
    protected function getMessage(array $record): LoggerMessage
    {
        if ($this->style == 'card') {
            $facts = [];
            $exceptions = [];
            foreach($record['context'] as $name => $value) {
                if ($value instanceof \Exception) {
                    $exceptions[$name] = $value;
                    continue;
                }

                if (is_array($value) || is_object($value)) $value = "`".json_encode($value)."`";

                $facts[] = ['name' => $name, 'value' => $value];
            }

            $facts = array_merge($facts, [[
                'name'  => 'Timestamp',
                'value' => date('D, M d Y H:i:s e'),
            ]]);

            return $this->useCardStyling($record['level_name'], $record['message'], $facts, $exceptions);
        } else {
            return $this->useSimpleStyling($record['level_name'], $record['message']);
        }
    }

    /**
     * Styling message as simple message
     *
     * @param string $name
     * @param string $message
     * @param array  $facts
     */
    private function useCardStyling($name, $message, $facts, $exceptions): LoggerMessage
    {
        $loggerColour = new LoggerColour($name);

        return new LoggerMessage([
            'summary'    => $name . ($this->name ? ': ' . $this->name : ''),
            'themeColor' => (string) $loggerColour,
            'sections'   => array_merge([
                array_merge(config('teams.show_avatars', true) ? [
                    'activityTitle'    => $this->name,
                    'activitySubtitle' => $message,
                    'activityImage'    => (string) new LoggerAvatar($name),
                    'facts'            => $facts,
                    'markdown'         => true
                ] : [
                    'activityTitle'    => $this->name,
                    'activitySubtitle' => $message,
                    'facts'            => $facts,
                    'markdown'         => true
                ], config('teams.show_type', true) ? ['activitySubtitle' => '<span style="color:#' . (string) $loggerColour . '">' . $message . '</span>',] : [])
            ], array_values(array_map(function ($key, \Exception $ex) {
                return [
                    'activityTitle' => $key,
                    'activitySubtitle' => $ex->getMessage(),
                    // replacing single newlines with doubles because text is formatted as markdown and double-newlines
                    // force a line-break in markdown.
                    'activityText' => implode("\n\n", explode("\n", $ex->getTraceAsString())),
                    'facts' => [
                        ['name' => 'Code', 'value' => $ex->getCode()],
                        ['name' => 'File', 'value' => $ex->getFile()],
                        ['name' => 'Line', 'value' => $ex->getLine()],
                    ],
                    'startGroup' => true,
                    'markdown' => true
                ];
            }, array_keys($exceptions), $exceptions)))
        ]);
    }

    /**
     * Styling message as simple message
     *
     * @param string $name
     * @param string $message
     */
    private function useSimpleStyling($name, $message): LoggerMessage
    {
        $loggerColour = new LoggerColour($name);

        return new LoggerMessage([
            'text'       => ($this->name ? $this->name . ' - ' : '') . '<span style="color:#' . (string) $loggerColour . '">' . $name . '</span>: ' . $message,
            'themeColor' => (string) $loggerColour,
        ]);
    }

    /**
     * @param array $record
     */
    protected function write(array $record): void
    {
        $json = json_encode($this->getMessage($record));

        $ch = curl_init($this->url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($json)
        ]);

        curl_exec($ch);
    }
}
