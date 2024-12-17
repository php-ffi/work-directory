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
final class WindowsThreadSafeDriver extends ThreadSafeDriver
{
    /**
     * @var non-empty-string
     */
    private const DEFAULT_INTERNAL_ENCODING = 'UTF-8';

    /**
     * @var int<1, max>
     *
     * @link https://learn.microsoft.com/ru-ru/windows/win32/fileio/maximum-file-path-limitation?tabs=registry
     */
    private const DEFAULT_EXPECTED_BUFFER_SIZE = 260;

    /**
     * @var non-empty-string
     */
    private const KERNEL32 = <<<'CLANG'
        extern int SetDllDirectoryA(const char* lpPathName);
        extern int SetDllDirectoryW(uint16_t* lpPathName);
        extern unsigned long GetDllDirectoryA(unsigned long nBufferLength, char* lpBuffer);
        extern unsigned long GetDllDirectoryW(unsigned long nBufferLength, uint16_t* lpBuffer);
        CLANG;

    /**
     * @var \FFI&object{
     *     SetDllDirectoryA: callable(string|CData): int,
     *     SetDllDirectoryW: callable(CData): int,
     *     GetDllDirectoryA: callable(int, string|CData): int,
     *     GetDllDirectoryW: callable(int, CData): int
     * }
     */
    private \FFI $ffi;

    /**
     * @var non-empty-string
     */
    private string $internal;

    /**
     * @var non-empty-string
     */
    private string $external;

    public function __construct()
    {
        parent::__construct();

        $this->internal = $this->getDefaultInternalEncoding();
        $this->external = $this->getDefaultExternalEncoding();

        $this->boot();
    }

    /**
     * @return non-empty-string
     */
    private function getDefaultInternalEncoding(): string
    {
        /**
         * @var non-empty-string
         * @phpstan-ignore-next-line : Allow non-strict short ternary operator
         */
        return \mb_internal_encoding() ?: self::DEFAULT_INTERNAL_ENCODING;
    }

    /**
     * @return non-empty-string
     */
    private function getDefaultExternalEncoding(): string
    {
        /** @var array{1: int} $unpacked */
        $unpacked = \unpack('S', "\x01\x00");

        return 'UTF-16' . ($unpacked[1] === 1 ? 'LE' : 'BE');
    }

    private function boot(): void
    {
        // @phpstan-ignore-next-line : FFI object assigment
        $this->ffi = \FFI::cdef(self::KERNEL32, 'kernel32.dll');
    }

    public function get(): ?string
    {
        $bufferSizeDiv2 = self::DEFAULT_EXPECTED_BUFFER_SIZE;
        // @phpstan-ignore-next-line : FFI object "new" call PHPStan false-positive
        $uint16Array = $this->ffi->new("uint16_t[$bufferSizeDiv2]", false);
        // @phpstan-ignore-next-line : FFI object "addr" call PHPStan false-positive
        $uint16ArrayPointer = \FFI::addr($uint16Array[0]);

        // @phpstan-ignore-next-line : Method not found PHPStan false-positive
        $length = (int) $this->ffi->GetDllDirectoryW(self::DEFAULT_EXPECTED_BUFFER_SIZE, $uint16Array);
        $result = null;

        if ($length !== 0) {
            // @phpstan-ignore-next-line : PHPStan false-positive
            $char8Array = $this->ffi->cast('char*', $uint16ArrayPointer);
            // @phpstan-ignore-next-line : PHPStan false-positive
            $char8ArrayPointer = \FFI::addr($char8Array[0]);

            $result = \FFI::string($char8ArrayPointer, $length * 2);
            $result = \mb_convert_encoding($result, $this->internal, $this->external);
        }

        try {
            // Allow short ternary operation
            // @phpstan-ignore ternary.shortNotAllowed
            return $result ?: $this->fallback;
        } finally {
            \FFI::free($uint16Array);
        }
    }

    public function set(string $directory): bool
    {
        if (\mb_detect_encoding($directory, 'ASCII', true) !== false) {
            // @phpstan-ignore-next-line : PHPStan false-positive
            return $this->ffi->SetDllDirectoryA($directory) !== 0;
        }

        $directory = \mb_convert_encoding($directory, $this->external, $this->internal) . "\0\0";

        $bytes = \strlen($directory);
        // @phpstan-ignore-next-line : PHPStan false-positive
        $charArray = $this->ffi->new("char[$bytes]", false);
        // @phpstan-ignore-next-line : PHPStan false-positive
        $charArrayPointer = \FFI::addr($charArray[0]);

        \FFI::memcpy($charArrayPointer, $directory, $bytes);

        $bytesDiv2 = (int) \ceil($bytes / 2);
        $uint16Array = \FFI::cast("uint16_t[$bytesDiv2]", $charArray);
        // @phpstan-ignore-next-line : PHPStan false-positive
        $uint16ArrayPointer = \FFI::addr($uint16Array[0]);

        try {
            // @phpstan-ignore-next-line : Method not found PHPStan false-positive
            return $this->ffi->SetDllDirectoryW(\FFI::addr($uint16Array[0])) !== 0;
        } finally {
            \FFI::free($uint16ArrayPointer);
        }
    }

    /**
     * @return array{
     *     internal: non-empty-string,
     *     external: non-empty-string,
     *     ...
     * }
     * @phpstan-ignore-next-line : PHPStan false-positive
     */
    public function __serialize(): array
    {
        return \array_merge(parent::__serialize(), [
            'internal' => $this->internal,
            'external' => $this->external,
        ]);
    }

    /**
     * @param array{
     *     internal?: non-empty-string,
     *     external?: non-empty-string,
     *     ...
     * } $data
     */
    public function __unserialize(array $data): void
    {
        parent::__unserialize($data);

        $this->internal = $data['internal'] ?? $this->getDefaultInternalEncoding();
        $this->external = $data['external'] ?? $this->getDefaultExternalEncoding();

        $this->boot();
    }
}
