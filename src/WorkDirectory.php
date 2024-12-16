<?php

declare(strict_types=1);

namespace FFI\WorkDirectory;

use FFI\WorkDirectory\Driver\DriverInterface;
use FFI\WorkDirectory\Driver\LinuxThreadSafeDriver;
use FFI\WorkDirectory\Driver\MacOSThreadSafeDriver;
use FFI\WorkDirectory\Driver\NonThreadSafeDriver;
use FFI\WorkDirectory\Driver\WindowsThreadSafeDriver;

final class WorkDirectory
{
    private static ?DriverInterface $driver = null;

    private static function driver(): DriverInterface
    {
        if (self::$driver === null) {
            if (\PHP_ZTS === 0) {
                return self::$driver = new NonThreadSafeDriver();
            }

            switch (\PHP_OS_FAMILY) {
                case 'Windows':
                    return self::$driver = new WindowsThreadSafeDriver();

                case 'Darwin':
                    return self::$driver = new MacOSThreadSafeDriver();

                default:
                    return self::$driver = new LinuxThreadSafeDriver();
            }
        }

        return self::$driver;
    }

    /**
     * Gets the current working directory.
     *
     * Note: On some Unix variants, getcwd will not return correct result if
     * any one of the parent directories does not have the readable or search
     * mode set, even if the current directory does. See chmod for more
     * information on modes and permissions.
     *
     * @return non-empty-string|null the current working directory on success,
     *         or {@see null} on failure
     */
    public static function get(): ?string
    {
        $driver = self::driver();

        return $driver->get();
    }

    /**
     * Change directory.
     *
     * @psalm-taint-sink file $directory
     * @param non-empty-string $directory the new current directory
     *
     * @return bool returns {@see true} on success or {@see false} on failure
     */
    public static function set(string $directory): bool
    {
        $driver = self::driver();

        return $driver->set($directory);
    }
}
