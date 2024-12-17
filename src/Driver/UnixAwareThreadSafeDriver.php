<?php

declare(strict_types=1);

namespace FFI\WorkDirectory\Driver;

use FFI\CData;

/**
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal FFI\WorkDirectory
 *
 * @psalm-suppress all
 */
abstract class UnixAwareThreadSafeDriver extends ThreadSafeDriver
{
    /**
     * @var non-empty-string
     */
    private const STDLIB = <<<'CLANG'
        char *getenv(const char *name);
        int setenv(const char *name, const char *value, int overwrite);
        CLANG;

    /**
     * @var \FFI&object{
     *     getenv: callable(string|CData): string,
     *     setenv: callable(string|CData, string|CData, int): int
     * }
     */
    private \FFI $ffi;

    public function __construct()
    {
        parent::__construct();

        $this->boot();
    }

    private function boot(): void
    {
        // @phpstan-ignore-next-line : PHPStan false-positive
        $this->ffi = \FFI::cdef(self::STDLIB);
    }

    /**
     * @return non-empty-string
     */
    abstract protected static function getEnvVariableName(): string;

    public function get(): ?string
    {
        /**
         * Note: The getenv function returns a pointer to a string associated
         * with the matched list member. The string pointed to shall not be
         * modified by the program, but may be overwritten by a subsequent
         * call to the getenv function.
         *
         * @var CData|null $directory
         * @phpstan-ignore-next-line : PHPStan false-positive
         */
        $directory = $this->ffi->getenv(static::getEnvVariableName());

        if ($directory === null) {
            return $this->fallback;
        }

        // Allow short ternary operator
        // @phpstan-ignore ternary.shortNotAllowed
        return \FFI::string($directory) ?: $this->fallback;
    }

    public function set(string $directory): bool
    {
        // @phpstan-ignore-next-line : PHPStan false-positive
        return $this->ffi->setenv(static::getEnvVariableName(), $directory, 1) === 0;
    }

    public function __unserialize(array $data): void
    {
        parent::__unserialize($data);

        $this->boot();
    }
}
