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

    /**
     * Returns the first entry of the variable, which is the directory that
     * was set last (see {@see set()}).
     */
    public function get(): ?string
    {
        $directories = $this->getDirectories();

        return $directories[0] ?? $this->fallback;
    }

    /**
     * Prepends the directory to the list of already registered directories
     * instead of replacing them.
     */
    public function set(string $directory): bool
    {
        $directories = \array_diff($this->getDirectories(), [$directory]);
        \array_unshift($directories, $directory);

        // @phpstan-ignore-next-line : PHPStan false-positive
        return $this->ffi->setenv(static::getEnvVariableName(), \implode(':', $directories), 1) === 0;
    }

    /**
     * @return list<non-empty-string>
     */
    private function getDirectories(): array
    {
        /**
         * Note: The getenv function returns a pointer to a string associated
         * with the matched list member. The string pointed to shall not be
         * modified by the program, but may be overwritten by a subsequent
         * call to the getenv function.
         *
         * @var CData|null $value
         *
         * @phpstan-ignore-next-line : PHPStan false-positive
         */
        $value = $this->ffi->getenv(static::getEnvVariableName());

        if ($value === null) {
            return [];
        }

        return \array_values(\array_filter(
            \explode(':', \FFI::string($value)),
            static fn(string $entry): bool => $entry !== '',
        ));
    }

    public function __unserialize(array $data): void
    {
        parent::__unserialize($data);

        $this->boot();
    }
}
