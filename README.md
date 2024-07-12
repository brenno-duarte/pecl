# PHP PECL Component

<img src="https://pecl.php.net/img/peclsmall.gif">

PECL is a repository for PHP Extensions, providing a directory of all known extensions and hosting facilities for downloading and development of PHP extensions.

This component is an alternative to the original PECL located on the website [https://pecl.php.net/](https://pecl.php.net/).

The packaging and distribution system used by PECL can be created using the `build.php` file or by downloading the `pecl.phar` file.

**NOTE: Not all extensions that exist on the official PECL website are contained in this repository (those that do not have a DLL or are no longer supported), only the most used ones.**

## Requirements

The PECL component is supported from PHP version 8.3 and 64-bit Windows.

## Installation

Simply download the `pecl.phar` file. You can use one of the following commands:

`install` - Installs the package,
`status` - Shows if extension is enabled,
`info` - Shows the extension description,
`list` - Shows all available commands,
`version` - Shows extension version

Example

```sh
php pecl.phar install memcached
```

## List of supported extensions

This PECL component does not use the official repository [https://pecl.php.net/](https://pecl.php.net/). Instead, it uses the `extensions/` folder to download the DLL files.

You can check all supported extensions by going to the `extensions/` folder.

## Polyfills

The PECL component is only supported on Windows operating systems. However, you can install the [brenno-duarte/php-pecl-extensions](https://github.com/brenno-duarte/php-pecl-extensions) component via Composer and use some polyfills that exist in it. This component also allows you to install DLL files.

# License

MIT