<?php

declare(strict_types=1);

namespace FFI\WorkDirectory\Driver;

/**
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal FFI\WorkDirectory
 */
final class NonThreadSafeDriver implements DriverInterface
{
    public function get(): ?string
    {
        // Allow short ternary operator
        // @phpstan-ignore ternary.shortNotAllowed
        return \getcwd() ?: null;
    }

    public function set(string $directory): bool
    {
        // Checked in advance: chdir() emits a warning on failure, the
        // thread-safe drivers fail silently.
        if (!\is_dir($directory)) {
            return false;
        }

        return \chdir($directory);
    }
}
