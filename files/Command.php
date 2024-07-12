<?php

require_once "ConsoleOutput.php";

class Command
{
    const VERSION = "0.1.1";
    const OS_UNKNOWN = "Unknown";
    const OS_WIN = "Windows";
    const OS_LINUX = "Linux";
    const OS_OSX = "MacOS";

    private array $php_info = [];

    public function __construct(string $command, ?string $extension)
    {
        $this->PhpInfo();

        if ($command == "list") $this->list();
        if ($command == "about") $this->about();

        if ($command == "install") {
            $this->extensionNameExistsInInput($command, $extension);
            $this->install($extension);
        }

        if ($command == "status") {
            $this->extensionNameExistsInInput($command, $extension);
            $this->status($extension);
        }

        if ($command == "info") {
            $this->extensionNameExistsInInput($command, $extension);
            $this->info($extension);
        }
        
        if ($command == "version") {
            $this->extensionNameExistsInInput($command, $extension);
            $this->version($extension);
        }

        //if ($command == "self-update") $this->selfUpdate();
    }

    private function extensionNameExistsInInput(string $command, ?string $extension): void
    {
        if (is_null($extension)) {
            ConsoleOutput::error("You must enter the name of the extension for this command")->print()->break();
            ConsoleOutput::line("Example: php pecl.phar " . $command . " <extension_name>")->print()->break()->exit();
        }
    }

    private function install(string $extension): void
    {
        if (extension_loaded($extension)) {
            ConsoleOutput::success($extension . ": extension already installed")->print()->exit();
        }

        $url = "https://raw.githubusercontent.com/brenno-duarte/php-pecl/main/extensions/" .
            $extension . "/" . $extension . "-" . $this->php_info['php_version'] . "-x64-" .
            $this->php_info['thread_safe'] . "/php_" . $extension . ".dll";

        $extension_dir_file = $this->php_info["extensions_dir"] . "php_" . $extension . ".dll";
        $extension_dir_temp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "php_" . $extension . ".dll";
        $data = @fopen($url, "r");

        if ($data == false) ConsoleOutput::error("Extension `" . $extension . "` not found on repository")->print()->exit();
        $file = file_put_contents($extension_dir_temp, $data);

        if ($file == false) ConsoleOutput::error("Failed to create DLL file")->print()->exit();
        $is_moved = rename($extension_dir_temp, $extension_dir_file);

        if ($is_moved == true && is_file($extension_dir_file)) {
            ConsoleOutput::success($extension . ": extension installed")->print();

            if (file_exists($extension_dir_temp)) unlink($extension_dir_temp);
            clearstatcache();
            $this->isExtensionEnabled($extension);
        }

        ConsoleOutput::warning($extension . ": extension not installed")->print()->break();
    }

    private function status(string $name): void
    {
        if (is_file($this->php_info["extensions_dir"] . "php_" . $name . ".dll")) {
            ConsoleOutput::success($name . ": extension installed")->print();
            $this->isExtensionEnabled($name);
        }

        ConsoleOutput::warning($name . ": extension not installed")->print()->break()->exit();
    }

    private function list(): void
    {
        ConsoleOutput::info("PECL Commands")->print()->break(true);
        ConsoleOutput::formattedRowData([
            "install" => "Installs the package",
            "status" => "Shows if extension is enabled",
            "info" => "Shows the extension description",
            "list" => "Shows all available commands",
            "version" => "Shows extension version"
        ], 15, true);
    }

    private function version(string $extension_name): void
    {
        try {
            $extension = new ReflectionExtension($extension_name);

            ConsoleOutput::success("Extension: ")->print();
            ConsoleOutput::line($extension->getName())->print()->break();
            ConsoleOutput::success("Version: ")->print();
            ConsoleOutput::line($extension->getVersion())->print();
        } catch (ReflectionException $e) {
            ConsoleOutput::error($e->getMessage())->print()->break();
        }
    }

    private function selfUpdate(): void
    {
        //$url = "https://github.com/brenno-duarte/php-pecl-extensions/releases/download/3.4.0alpha1/php_xdebug-3.4.0alpha1-8.3-vs16-x86_64.dll"
    }

    private function about(): void
    {
        ConsoleOutput::info("PHP PECL Component")->print()->break(true);

        ConsoleOutput::formattedRowData([
            "Version" => self::VERSION,
            "Repository" => "https://github.com/brenno-duarte/php-pecl/"
        ], 15, true);
    }

    public static function getOS(): string
    {
        switch (true) {
            case stristr(PHP_OS, 'DAR'):
                return self::OS_OSX;
            case stristr(PHP_OS, 'WIN'):
                return self::OS_WIN;
            case stristr(PHP_OS, 'LINUX'):
                return self::OS_LINUX;
            default:
                return self::OS_UNKNOWN;
        }
    }

    private function PhpInfo(): array
    {
        if (ZEND_THREAD_SAFE == true) {
            $this->php_info['thread_safe'] = 'ts';
        } else {
            $this->php_info['thread_safe'] = 'nts';
        }

        $compiler = shell_exec("php -i | findstr Compiler");
        $compiler = explode("=>", trim($compiler));

        $this->php_info['compiler'] = trim($compiler[1]);
        $this->php_info['php_version'] = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
        $this->php_info['extensions_dir'] = $this->PhpExtensionDir();

        return $this->php_info;
    }

    private function isExtensionEnabled(string $name): never
    {
        if (extension_loaded($name) == true) {
            ConsoleOutput::success(' and enabled on "php.ini"')->print()->break();
        } else {
            ConsoleOutput::success(', but not enabled. Add this line on your "php.ini":')->print()->break(true);
            $this->configIniComponent($name);
        }

        exit;
    }

    private function configIniComponent(string $name): void
    {
        $url = "https://github.com/brenno-duarte/pecl/tree/main/extensions-required-files/";

        switch ($name) {
            case "apcu":
                ConsoleOutput::info("[apcu]")->print()->break();
                ConsoleOutput::info("extension=apcu")->print()->break();
                ConsoleOutput::info("apc.enabled=1")->print()->break();
                ConsoleOutput::info("apc.shm_size=32M")->print()->break();
                ConsoleOutput::info("apc.ttl=7200")->print()->break();
                ConsoleOutput::info("apc.enable_cli=1")->print()->break();
                ConsoleOutput::info("apc.serializer=php")->print();
                break;

            case "pcov":
                ConsoleOutput::info("[pcov]")->print()->break();
                ConsoleOutput::info("extension=pcov")->print()->break();
                ConsoleOutput::info("pcov.enabled=1")->print()->break();
                ConsoleOutput::info("pcov.directory=/path/to/your/source/directory")->print();
                break;

            case "imagick":
                ConsoleOutput::info("[imagick]")->print()->break();
                ConsoleOutput::info("extension=imagick")->print()->break();

                if (self::getOS() == "Windows") {
                    echo PHP_EOL;
                    ConsoleOutput::success("See this link " . $url . "imagick/imagick-" . $this->php_info["php_version"] . "-x64-" . $this->php_info["thread_safe"] . "/")->print()->break();
                    ConsoleOutput::success("And copy all DLL files into the PHP installation directory (in the same directory as `php.exe`)")->print();
                }
                break;

            case "yac":
                ConsoleOutput::info("[yac]")->print()->break();
                ConsoleOutput::info("extension=yac")->print()->break();
                ConsoleOutput::info("yac.enable_cli=1")->print();
                break;

            case "xhprof":
                ConsoleOutput::info("[xhprof]")->print()->break();
                ConsoleOutput::info("extension=xhprof")->print()->break();
                ConsoleOutput::info("xhprof.output_dir=/tmp/xhprof")->print()->break();
                ConsoleOutput::info("xhprof.sampling_interval=100000")->print()->break();
                ConsoleOutput::info("xhprof.collect_additional_info=0")->print()->break(true);
                ConsoleOutput::success("See aditional files in " . $url . "xhprof/")->print();
                break;

            default:
                ConsoleOutput::info("extension=" . $name)->print();
                break;
        }
    }

    private function PhpExtensionDir(): string
    {
        $ini_file = php_ini_loaded_file();
        $extension_dir = ini_get("extension_dir");

        if ($extension_dir != "ext") {
            $ext_dir = $extension_dir;
        } else {
            $ext_dir = dirname($ini_file) . DIRECTORY_SEPARATOR . 'ext' . DIRECTORY_SEPARATOR;
        }

        return $ext_dir;
    }

    private function info(string $extension_name): void
    {
        try {
            $extension = new ReflectionExtension($extension_name);
            //$extension->info();

            ConsoleOutput::success("\nExtension name: ")->print();
            ConsoleOutput::line($extension->getName())->print()->break();

            ConsoleOutput::success("Version: ")->print();
            ConsoleOutput::line($extension->getVersion())->print()->break();

            ConsoleOutput::success("Is persistent: ")->print();
            echo ($extension->isPersistent()) ? "true" : "false";
            echo PHP_EOL;

            ConsoleOutput::success("Is temporary: ")->print();
            echo ($extension->isTemporary()) ? "true" : "false";
            echo PHP_EOL;

            ConsoleOutput::success("Support: ")->print();
            echo (extension_loaded($extension_name)) ? "enabled" : "disabled";
            echo PHP_EOL;

            if (!empty($extension->getDependencies())) {
                ConsoleOutput::success("Dependencies")->print()->break();

                foreach ($extension->getDependencies() as $key => $value) {
                    echo "\n {$key} => {$value}";
                }

                echo PHP_EOL . PHP_EOL;
            }

            if (!empty($extension->getClassNames())) {
                ConsoleOutput::success("Class names: ")->print()->break();
                echo "\n ", implode("\n ", $extension->getClassNames());
                echo PHP_EOL . PHP_EOL;
            }

            if (!empty($extension->getConstants())) {
                ConsoleOutput::success("\nConstants")->print()->break();

                foreach ($extension->getConstants() as $key => $value) {
                    echo "\n {$key} => {$value}";
                }

                echo PHP_EOL . PHP_EOL;
            }

            if (!empty($extension->getFunctions())) {
                ConsoleOutput::success("Functions")->print()->break();
                echo "\n ", implode("\n ", array_keys($extension->getFunctions()));
                echo PHP_EOL . PHP_EOL;
            }

            if (!empty($extension->getINIEntries())) {
                ConsoleOutput::success("php.ini entries")->print()->break();

                foreach ($extension->getINIEntries() as $key => $value) {
                    echo "\n {$key} => {$value}";
                }

                echo PHP_EOL . PHP_EOL;
            }
        } catch (ReflectionException $e) {
            ConsoleOutput::error($e->getMessage())->print()->break();
        }
    }
}
