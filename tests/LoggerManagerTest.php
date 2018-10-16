<?php declare(strict_types = 1);

namespace Tests\Contributte\Monolog;

use Contributte\Monolog\Exception\Logic\InvalidStateException;
use Contributte\Monolog\LoggerManager;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class LoggerManagerTest extends TestCase
{

	/** @var LoggerManager */
	private $manager;

	protected function setUp(): void
	{
		$this->manager = new LoggerManager();
	}

	public function testHas(): void
	{
		$this->manager->add(new Logger('foo'));
		$this->assertTrue($this->manager->has('foo'));
	}

	public function testAddException(): void
	{
		$this->expectException(InvalidStateException::class);
		$this->expectExceptionMessage('Cannot add logger with name "foo". Logger with same name is already defined.');
		$this->manager->add(new Logger('foo'));
		$this->manager->add(new Logger('foo'));
	}

	public function testGet(): void
	{
		$logger = new Logger('foo');
		$this->manager->add($logger);
		$this->assertEquals($logger, $this->manager->get('foo'));
	}

	public function testGetException(): void
	{
		$this->expectException(InvalidStateException::class);
		$this->expectExceptionMessage('Cannot get undefined logger "foo".');
		$this->manager->get('foo');
	}

}
