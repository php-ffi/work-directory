<?php

declare(strict_types=1);

namespace FFI\WorkDirectory\Tests;

use FFI\WorkDirectory\Driver\DriverInterface;
use FFI\WorkDirectory\Driver\LinuxThreadSafeDriver;
use FFI\WorkDirectory\Driver\MacOSThreadSafeDriver;
use FFI\WorkDirectory\Driver\NonThreadSafeDriver;
use FFI\WorkDirectory\Driver\WindowsThreadSafeDriver;
use FFI\WorkDirectory\Tests\Stub\WorkDirectoryFacadeStub;
use FFI\WorkDirectory\WorkDirectory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;

class WorkDirectoryTest extends TestCase
{
    /**
     * @var non-empty-string|null
     */
    private ?string $initial = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->initial = \getcwd() ?: null;
    }

    protected function tearDown(): void
    {
        if ($this->initial !== null) {
            \chdir($this->initial);
        }

        parent::tearDown();
    }

    /**
     * @return iterable<non-empty-string, DriverInterface>
     */
    private static function getDrivers(): iterable
    {
        yield WorkDirectory::class => new WorkDirectoryFacadeStub();

        yield NonThreadSafeDriver::class => new NonThreadSafeDriver();

        switch (\PHP_OS_FAMILY) {
            case 'Windows':
                yield WindowsThreadSafeDriver::class => new WindowsThreadSafeDriver();
                break;

            case 'Darwin':
                yield MacOSThreadSafeDriver::class => new MacOSThreadSafeDriver();
                break;

            default:
                yield LinuxThreadSafeDriver::class => new LinuxThreadSafeDriver();
                break;
        }
    }

    public static function driversDataProvider(): array
    {
        $result = [];

        foreach (self::getDrivers() as $name => $driver) {
            $result[$name] = [$driver];
        }

        return $result;
    }

    /**
     * @testdox Checks that the default value in any driver is equivalent to the working directory at initialization.
     *
     * @dataProvider driversDataProvider
     */
    #[DataProvider('driversDataProvider')]
    #[TestDox('Checks that the default value in any driver is equivalent to the working directory at initialization.')]
    public function testDefaultEqualsToGlobal(DriverInterface $driver): void
    {
        self::assertSame(\getcwd() ?: null, $driver->get());
    }

    /**
     * @testdox Checks that the work directory value in any driver is modifiable.
     *
     * @dataProvider driversDataProvider
     */
    #[DataProvider('driversDataProvider')]
    #[TestDox('Checks that the work directory value in any driver is modifiable.')]
    public function testModifyIsAvailable(DriverInterface $driver): void
    {
        $expected = \realpath(__DIR__ . '/chdir-ascii');

        self::assertTrue($driver->set($expected));
        self::assertSame($expected, $driver->get());
    }

    /**
     * @testdox Checks that the work directory value in any driver is modifiable by unicode string.
     *
     * @dataProvider driversDataProvider
     */
    #[DataProvider('driversDataProvider')]
    #[TestDox('Checks that the work directory value in any driver is modifiable by unicode string.')]
    public function testModifyIsAvailableUsingUnicode(DriverInterface $driver): void
    {
        $expected = \realpath(__DIR__ . '/chdir-unicode-привет-мир');

        self::assertTrue($driver->set($expected));
        self::assertSame($expected, $driver->get());
    }

    /**
     * @testdox Checks that a directory that does not exist is rejected by any driver.
     *
     * @dataProvider driversDataProvider
     */
    #[DataProvider('driversDataProvider')]
    #[TestDox('Checks that a directory that does not exist is rejected by any driver.')]
    public function testNonExistentDirectoryIsRejected(DriverInterface $driver): void
    {
        $before = $driver->get();

        self::assertFalse($driver->set(__DIR__ . '/does-not-exist'));
        self::assertSame($before, $driver->get());
    }

    /**
     * @testdox Checks that a file is rejected by any driver.
     *
     * @dataProvider driversDataProvider
     */
    #[DataProvider('driversDataProvider')]
    #[TestDox('Checks that a file is rejected by any driver.')]
    public function testFileIsRejected(DriverInterface $driver): void
    {
        self::assertFalse($driver->set(__FILE__));
    }

    /**
     * @testdox Checks that a relative directory is resolved to an absolute one by any driver.
     *
     * @dataProvider driversDataProvider
     */
    #[DataProvider('driversDataProvider')]
    #[TestDox('Checks that a relative directory is resolved to an absolute one by any driver.')]
    public function testRelativeDirectoryIsResolved(DriverInterface $driver): void
    {
        \chdir(__DIR__);

        self::assertTrue($driver->set('chdir-ascii'));
        self::assertSame(\realpath(__DIR__ . '/chdir-ascii'), $driver->get());
    }

    /**
     * @testdox Checks that on a non thread-safe build the facade changes the working directory of the process.
     */
    #[TestDox('Checks that on a non thread-safe build the facade changes the working directory of the process.')]
    public function testFacadeChangesProcessDirectoryOnNts(): void
    {
        if (\PHP_ZTS) {
            self::markTestSkipped('Thread-safe build: the process directory is not changed by design');
        }

        $expected = \realpath(__DIR__ . '/chdir-ascii');

        self::assertTrue(WorkDirectory::set($expected));
        self::assertSame($expected, \getcwd());
    }
}
