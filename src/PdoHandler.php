<?php

namespace kafene\Monolog\Handler;

use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;
use PDO;

class PdoHandler extends AbstractProcessingHandler
{
    protected $pdo;
    protected $table;
    protected $pdoStatement;

    public function __construct(PDO $pdo, $table = 'monolog', $level = Logger::DEBUG, $bubble = true)
    {
        $this->pdo = $pdo;
        $this->table = $table;

        parent::__construct($level, $bubble);
    }

    public function write(array $record)
    {
        $data = [
            ':channel'    => (string)  $record['channel'],
            ':message'    => (string)  $record['message'],
            ':level'      => (integer) $record['level'],
            ':level_name' => (string)  $record['level_name'],
            ':formatted'  => (string)  $record['formatted'],
            ':file'       => 'unknown',
            ':line_no'    => 0,
            ':time'       => $record['datetime']->format('U'),
        ];

        if (isset($record['extra']['file'])) {
            $data['file'] = (string) $record['extra']['file'];
        }

        if (isset($record['extra']['line'])) {
            $data['line_no'] = (integer) $record['extra']['line'];
        }

        $this->getPdoStatement()->execute($data);
    }

    public function getPdoStatement()
    {
        if (null === $this->pdoStatement) {
            $this->pdo->exec(sprintf(
                'CREATE TABLE IF NOT EXISTS "%s" (
                    "id"         INTEGER PRIMARY KEY,
                    "channel"    TEXT,
                    "message"    TEXT,
                    "level"      TEXT,
                    "level_name" TEXT,
                    "formatted"  TEXT,
                    "file"       TEXT,
                    "line"       INTEGER,
                    "time"       INTEGER
                )',
                $this->table
            ));

            $this->pdoStatement = $this->pdo->prepare(sprintf(
                'INSERT INTO "%s" (
                    "channel",
                    "message",
                    "level",
                    "level_name",
                    "formatted",
                    "file",
                    "line",
                    "time"
                ) VALUES (
                    :channel,
                    :message,
                    :level,
                    :level_name,
                    :formatted,
                    :file,
                    :line_no,
                    :time
                )',
                $this->table
            ));
        }

        return $this->pdoStatement;
    }
}
