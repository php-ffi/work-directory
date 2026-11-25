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
            // PHP_ZTS is int(0|1) up to PHP 8.3 and bool since PHP 8.4.
            if ((bool) \PHP_ZTS === false) {
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
     * Gets the directory the shared libraries are loaded from.
     *
     * On a non thread-safe build it is the working directory of the process
     * ({@see \getcwd()}). On a thread-safe build it is the directory passed to
     * the last successful {@see WorkDirectory::set()} call, or the working
     * directory at the moment the library was first used.
     *
     * Note: On some Unix variants, getcwd will not return correct result if
     * any one of the parent directories does not have the readable or search
     * mode set, even if the current directory does. See chmod for more
     * information on modes and permissions.
     *
     * @return non-empty-string|null the directory on success, or {@see null}
     *         on failure
     */
    public static function get(): ?string
    {
        $driver = self::driver();

        return $driver->get();
    }

    /**
     * Sets the directory the shared libraries are loaded from.
     *
     * A relative directory is resolved against the current working directory.
     * Returns {@see false} without changing anything when the directory does
     * not exist or is not a directory.
     *
     * @psalm-taint-sink file $directory
     * @param non-empty-string $directory the new directory
     * @return bool returns {@see true} on success or {@see false} on failure
     */
    public static function set(string $directory): bool
    {
        $driver = self::driver();

        return $driver->set($directory);
    }
}
