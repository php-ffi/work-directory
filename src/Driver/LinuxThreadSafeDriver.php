<?php

declare(strict_types=1);

namespace FFI\WorkDirectory\Driver;

/**
 * @internal This is an internal library class, please do not use it in your code.
 * @psalm-internal FFI\WorkDirectory
 */
final class LinuxThreadSafeDriver extends UnixAwareThreadSafeDriver
{
    /**
     * @var non-empty-string
     */
    private const ENV_NAME = 'LD_LIBRARY_PATH';

    protected static function getEnvVariableName(): string
    {
        return self::ENV_NAME;
    }
}
