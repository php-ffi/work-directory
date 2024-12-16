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
use PHPUnit\Framework\TestCase as BaseTestCase;

class WorkDirectoryTestCase extends BaseTestCase
{
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
    public function testDefaultEqualsToGlobal(DriverInterface $driver): void
    {
        self::assertSame(\getcwd() ?: null, $driver->get());
    }

    /**
     * @testdox Checks that the work directory value in any driver is modifiable.
     *
     * @dataProvider driversDataProvider
     */
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
    public function testModifyIsAvailableUsingUnicode(DriverInterface $driver): void
    {
        $expected = \realpath(__DIR__ . '/chdir-unicode-привет-мир');

        self::assertTrue($driver->set($expected));
        self::assertSame($expected, $driver->get());
    }
}
