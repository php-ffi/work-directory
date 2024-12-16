<?php

declare(strict_types=1);

namespace FFI\WorkDirectory\Tests\Stub;

use FFI\WorkDirectory\Driver\DriverInterface;
use FFI\WorkDirectory\WorkDirectory;

final class WorkDirectoryFacadeStub implements DriverInterface
{
    public function get(): ?string
    {
        return WorkDirectory::get();
    }

    public function set(string $directory): bool
    {
        return WorkDirectory::set($directory);
    }
}
