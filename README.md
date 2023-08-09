# Work Directory

<p align="center">
    <a href="https://packagist.org/packages/ffi/work-directory"><img src="https://poser.pugx.org/ffi/work-directory/require/php?style=for-the-badge" alt="PHP 8.1+"></a>
    <a href="https://packagist.org/packages/ffi/work-directory"><img src="https://poser.pugx.org/ffi/work-directory/version?style=for-the-badge" alt="Latest Stable Version"></a>
    <a href="https://packagist.org/packages/ffi/work-directory"><img src="https://poser.pugx.org/ffi/work-directory/v/unstable?style=for-the-badge" alt="Latest Unstable Version"></a>
    <a href="https://packagist.org/packages/ffi/work-directory"><img src="https://poser.pugx.org/ffi/work-directory/downloads?style=for-the-badge" alt="Total Downloads"></a>
    <a href="https://raw.githubusercontent.com/php-ffi/work-directory/master/LICENSE.md"><img src="https://poser.pugx.org/ffi/work-directory/license?style=for-the-badge" alt="License MIT"></a>
</p>
<p align="center">
    <a href="https://github.com/php-ffi/work-directory/actions"><img src="https://github.com/php-ffi/work-directory/workflows/build/badge.svg"></a>
</p>

In the case that during the loading of a binary (like `*.so`, `*.dylib` or `*.dll`)
through FFI it depends on some other binary module, then errors may occur if the
first one and dependent libraries are in different directories, like:

```php
// - bin/
//   - main.dll
//   - other/
//     - dependency.dll

$ffi = \FFI::cdef('...', __DIR__ . '/bin/main.dll');
// Error like "can not load ..."
// - In this case, an error occurs because the specified
//   dependency ("dependency.dll") could not be found in "bin"
//   or working directory. 
```

This library allows you to load similar dependencies:

```php
// Use "bin/other" directory for dependencies.
\FFI\WorkDirectory\WorkDirectory::set(__DIR__ . '/bin/other');

// 
$ffi = \FFI::cdef('...', __DIR__ . '/bin/main.dll');
```

You can also use the built-in [chdir function](https://www.php.net/manual/en/function.chdir.php) 
for such operations, however it will only work in case of a Non-Thread Safe PHP 
build ([see remark](https://www.php.net/manual/en/function.chdir.php#refsect1-function.chdir-notes)).

## Requirements

- PHP >= 7.4

## Installation

Library is available as composer repository and can be installed using the 
following command in a root of your project.

```sh
$ composer require ffi/work-directory
```

## Usage

### Get Current Work Directory

```php
$directory = \FFI\WorkDirectory\WorkDirectory::get();

if ($directory !== null) {
    echo 'CWD is: ' . $directory;
}
```

### Update Current Work Directory

Getting the full path to the library.

```php
$directory = __DIR__ . '/path/to/directory';

if (\FFI\WorkDirectory\WorkDirectory::set($directory)) {
    echo 'CWD has been updated to: ' . $directory;
}
```
