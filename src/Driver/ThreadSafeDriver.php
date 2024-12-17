<?php

declare(strict_types=1);

namespace FFI\WorkDirectory\Driver;

use FFI\Env\Runtime;

/**
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal FFI\WorkDirectory
 */
abstract class ThreadSafeDriver implements DriverInterface
{
    /**
     * @var non-empty-string|null
     */
    protected ?string $fallback;

    public function __construct()
    {
        Runtime::assertAvailable();

        $this->fallback = $this->getInitialCurrentWorkingDirectory();
    }

    /**
     * @return non-empty-string|null
     */
    private function getInitialCurrentWorkingDirectory(): ?string
    {
        if ((bool) ($directory = \getcwd()) !== false) {
            return $directory;
        }

        if (($directory = $this->fetchVariable('SCRIPT_NAME')) !== null) {
            return $directory;
        }

        if (($directory = $this->fetchVariable('SCRIPT_FILENAME')) !== null) {
            return $directory;
        }

        if (($directory = $this->fetchVariable('PHP_SELF')) !== null) {
            return $directory;
        }

        return null;
    }

    /**
     * @param non-empty-string $variable
     * @return non-empty-string|null
     */
    private function fetchVariable(string $variable): ?string
    {
        if (!isset($_SERVER[$variable]) || !\is_string($_SERVER[$variable])) {
            return null;
        }

        $directory = \dirname($_SERVER[$variable]);

        return $directory === '' ? null : $directory;
    }

    /**
     * @return array{
     *     fallback: non-empty-string|null,
     *     ...
     * }
     */
    public function __serialize(): array
    {
        return [
            'fallback' => $this->fallback,
        ];
    }

    /**
     * @param array{
     *     fallback?: non-empty-string|null,
     *     ...
     * } $data
     */
    public function __unserialize(array $data): void
    {
        $this->fallback = $data['fallback'] ?? $this->getInitialCurrentWorkingDirectory();
    }
}
