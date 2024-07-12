<?php

class ConsoleOutput
{
    protected static string $message;
    protected static string $color_reset = '';
    protected static string $color_success = '';
    protected static string $color_info = '';
    protected static string $color_warning = '';
    protected static string $color_error = '';
    protected static string $color_line = '';

    public static function success(mixed $message, bool $space = false): static
    {
        self::generateColors();
        self::$message = self::prepareColor($message, self::$color_success, $space);
        return new static;
    }

    public static function info(mixed $message, bool $space = false): static
    {
        self::generateColors();
        self::$message = self::prepareColor($message, self::$color_info, $space);
        return new static;
    }

    public static function warning(mixed $message, bool $space = false): static
    {
        self::generateColors();
        self::$message = self::prepareColor($message, self::$color_warning, $space);
        return new static;
    }

    public static function error(mixed $message, bool $space = false): static
    {
        self::generateColors();
        self::$message = self::prepareColor($message, self::$color_error, $space);
        return new static;
    }

    public static function line(mixed $message, bool $space = false): static
    {
        self::generateColors();
        self::$message = self::prepareColor($message, self::$color_line, $space);
        return new static;
    }

    public function print(): ConsoleOutput
    {
        echo self::$message;
        return $this;
    }

    public function getMessage(): string
    {
        return self::$message;
    }

    public function exit(): never
    {
        exit;
    }

    public function break(bool|int $repeat = false): ConsoleOutput
    {
        echo PHP_EOL;

        if (is_int($repeat)) {
            for ($i = 0; $i <= $repeat; $i++) {
                echo PHP_EOL;
            }
        }

        if ($repeat == true) {
            echo PHP_EOL . PHP_EOL;
        }

        return $this;
    }

    public static function formattedRowData(array $data, int $space = 30, bool $margin = false): void
    {
        ($margin == true) ? $margin_string = "  " : $margin_string = "";

        foreach ($data as $name => $value) {
            $value = self::validateData($value);
            echo $margin_string . self::$color_success . str_pad($name, $space) . self::$color_reset . $value . PHP_EOL;
        }
    }

    private static function validateData(mixed $value): mixed
    {
        if (!is_string($value)) {
            if (is_object($value)) {
                $value = get_class($value);
            }

            if (is_resource($value)) {
                $value = '[RESOURCE]';
            }

            if (is_callable($value)) {
                $value = '[CALLABLE]';
            }

            if (is_null($value)) {
                $value = '[NULL]';
            }

            if (is_bool($value)) {
                $value = $value === false ? '[FALSE]' : '[TRUE]';
            } else {
                $value = (string)$value;
            }
        }

        return $value;
    }

    private static function prepareColor(mixed $message, string $color, bool $space): string
    {
        $space_value = "";

        if ($space == true) {
            $space_value = "  ";
        }

        return $space_value . $color . $message . self::$color_reset;
    }

    protected static function generateColors(): bool
    {
        if (self::colorIsSupported() || self::are256ColorsSupported()) {
            self::$color_reset = "\e[0m";
            self::$color_success = "\033[92m";
            self::$color_info = "\033[96m";
            self::$color_warning = "\033[93m";
            self::$color_error = "\033[41m";
            self::$color_line = "\033[0;38m";

            return true;
        }

        return false;
    }

    private static function colorIsSupported(): bool
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            if (function_exists('sapi_windows_vt100_support') && sapi_windows_vt100_support(STDOUT)) {
                return true;
            } elseif (getenv('ANSICON') !== false || getenv('ConEmuANSI') === 'ON') {
                return true;
            }

            return false;
        } else {
            return function_exists('posix_isatty') && posix_isatty(STDOUT);
        }
    }

    private static function are256ColorsSupported(): bool
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            return function_exists('sapi_windows_vt100_support') && sapi_windows_vt100_support(STDOUT);
        } else {
            return str_starts_with(getenv('TERM'), '256color');
        }
    }
}