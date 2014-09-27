<?php

require_once dirname(__DIR__).'/vendor/autoload.php';

use Monolog\Logger;
use Monolog\Processor\IntrospectionProcessor;
use kafene\Monolog\Handler\PdoHandler;

class PdoHandlerText extends PHPUnit_Framework_TestCase
{
    /**
     * @var \Monolog\Logger
     */
    public $logger;

    /**
     * @var \kafene\Monolog\Handler\PdoHandler
     */
    public $handler;

    /**
     * @var \PDO
     */
    public $pdo;

    public function setUp()
    {
        $this->logger = new Logger('pdo_handler_test');

        $dsn = 'sqlite:'.dirname(__DIR__).'/log.sqlite';
        $table = 'logs';

        $this->pdo = new PDO($dsn, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        $this->pdo->exec('DELETE FROM logs');

        $this->handler = new PdoHandler($this->pdo, $table);

        $this->logger->pushHandler($this->handler);
    }

    public function testLogsToDatabase()
    {
        $this->logger->info('some information');

        $log = $this->pdo->query('SELECT * FROM logs')->fetch();

        $this->assertNotEmpty($log);

        $this->assertTrue(isset($log['message']));

        $this->assertEquals('some information', $log['message']);
    }

    public function testLogsLevelAndLevelName()
    {
        $this->logger->debug('debug message');
        $this->logger->critical('critical message');

        $sql = 'SELECT * FROM logs WHERE level_name = \'%s\'';

        $debugLog = $this->pdo->query(sprintf($sql, 'DEBUG'))->fetch();
        $criticalLog = $this->pdo->query(sprintf($sql, 'CRITICAL'))->fetch();

        $this->assertNotEmpty($debugLog);
        $this->assertNotEmpty($criticalLog);

        $this->assertEquals($debugLog['message'], 'debug message');
        $this->assertEquals($criticalLog['message'], 'critical message');
    }

    public function testLogsFileAndLineWhenUsingIntrospectionProcessor()
    {
        $this->logger->pushProcessor(new IntrospectionProcessor());

        $this->logger->warning('a warning'); $line = __LINE__;

        $log = $this->pdo->query('SELECT * FROM logs')->fetch();

        $this->assertTrue(isset($log['file']));
        $this->assertTrue(isset($log['line']));

        $this->assertEquals($log['message'], 'a warning');
        $this->assertEquals($log['file'], __FILE__);
        $this->assertEquals($log['line'], $line);
    }
}
