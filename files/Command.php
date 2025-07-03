<?php

require_once "ConsoleOutput.php";

class Command
{
    const VERSION = "0.3.0";
    const OS_UNKNOWN = "Unknown";
    const OS_WIN = "Windows";
    const OS_LINUX = "Linux";
    const OS_OSX = "MacOS";

    private array $php_info = [];
    private readonly string $repo_url;
    private readonly string $repo_api_url;
    private readonly string $repo_raw_url;
    private string $extension_dir_file = "";
    private string $extension_dir_temp = "";

    public function __construct(string $command, ?string $extension)
    {
        $this->PhpInfo();

        $repository = "brenno-duarte/pecl";
        $this->repo_url = "https://github.com/" . $repository . "/";
        $this->repo_api_url = "https://api.github.com/repos/" . $repository . "/";
        $this->repo_raw_url = "https://raw.githubusercontent.com/" . $repository . "/";

        if ($command == "list") $this->list();
        if ($command == "about") $this->about();
        if ($command == "self-update") $this->selfUpdate();

        if ($command == "install") {
            $this->extensionNameExistsInInput($command, $extension);
            $this->install($extension);
        }

        if ($command == "download") {
            $this->extensionNameExistsInInput($command, $extension);

            ConsoleOutput::success($extension . ": Downloading DLL file...")->print()->break();
            $result = $this->downloadFromGithub($extension, true);

            if ($result == false) $this->download($extension, false);
            exit;
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

        ConsoleOutput::error("Command `" . $command . "` not found")->print()->exit();
    }

    private function extensionNameExistsInInput(string $command, ?string $extension): void
    {
        if (is_null($extension)) {
            ConsoleOutput::error("You must enter the name of the extension for this command")->print()->break();
            ConsoleOutput::line("Example: php pecl.phar " . $command . " <extension_name>")->print()->break()->exit();
        }
    }

    private function install(string $extension_name): never
    {
        if (extension_loaded($extension_name)) {
            ConsoleOutput::success($extension_name . ": extension already installed and enabled")->print()->exit();
        }

        /* if (file_exists($this->php_info["extensions_dir"] . "php_" . $extension_name . ".dll")) {
            clearstatcache();
            $this->isExtensionLoaded($extension_name);
        } */

        $this->download($extension_name);
        ConsoleOutput::success($extension_name . ": moving DLL to `ext` folder")->print()->break();
        $is_moved = @rename($this->extension_dir_temp, $this->extension_dir_file);

        if ($is_moved == true && is_file($this->extension_dir_file)) {
            ConsoleOutput::success($extension_name . ": DLL moved to `ext` folder successfully")->print()->break();

            if (file_exists($this->extension_dir_temp)) unlink($this->extension_dir_temp);
            clearstatcache();
            $this->isExtensionLoaded($extension_name);
        }

        ConsoleOutput::warning($extension_name . ": extension not installed")->print()->break()->exit();
    }

    private function download(string $extension, bool $to_temp_dir = true)
    {
        $url = $this->repo_raw_url . "master/extensions/" .  $extension . "/" . $this->php_info['php_version'] .
            "-" . $this->php_info['thread_safe'] . "-" . $extension . ".dll";

        $this->extension_dir_file = $this->php_info["extensions_dir"] . "php_" . $extension . ".dll";
        $this->extension_dir_temp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "php_" . $extension . ".dll";
        $data = @fopen($url, "r");

        if ($data == false) ConsoleOutput::error("Extension `" . $extension . "` not found on repository")->print()->exit();

        if ($to_temp_dir == true) {
            $file = file_put_contents($this->extension_dir_temp, $data);
        } else {
            $file = file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . "php_" . $extension . ".dll", $data);
        }

        if ($file == false) {
            ConsoleOutput::error($extension . ": Failed to create DLL file")->print()->exit();
        } else {
            ConsoleOutput::success($extension . ": DLL file downloaded successfully")->print()->break();
        }
    }

    private function downloadFromGithub(string $extension_name, bool $to_temp_dir = true)
    {
        $packages = file_get_contents(dirname(__DIR__) . DIRECTORY_SEPARATOR . "packages.json");
        $packages = json_decode($packages, true);

        if (isset($packages[$extension_name])) {
            $package_link = $packages[$extension_name];

            $json = file_get_contents(
                "https://api.github.com/repos/" . $package_link["repo"] . "/releases",
                false
            );

            $result = json_decode($json);
            $assets_files = $result[0]->assets;
            $file = null;
            $link_download = null;

            foreach ($assets_files as $value) {
                if (
                    str_contains($value->name, $this->php_info["thread_safe"]) &&
                    str_contains($value->name, $this->php_info["php_version"]) &&
                    str_contains($value->name, $this->php_info["OSBits"])
                ) {
                    $link_download = $value->browser_download_url;
                    $file = $value->name;
                }
            }

            $result = file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . $file, fopen($link_download, "r"));

            if (pathinfo(__DIR__ . DIRECTORY_SEPARATOR . $file, PATHINFO_EXTENSION) == "zip") {
                $folder = basename($file, "zip");
                $zip = new ZipArchive();

                if ($zip->open(__DIR__ . DIRECTORY_SEPARATOR . $file) == true) {
                    $zip->extractTo(__DIR__ . DIRECTORY_SEPARATOR . $folder);
                    $zip->close();

                    $result = copy(
                        __DIR__ . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . "php_" . $extension_name . ".dll",
                        __DIR__ . DIRECTORY_SEPARATOR . "php_" . $extension_name . ".dll"
                    );

                    if ($result == true) {
                        echo "Moved with successfully";
                    } else {
                        echo "Error to move";
                    }
                } else {
                    echo "Error to extract files";
                }
            }
        }

        return false;
    }

    private function status(string $name): void
    {
        if (is_file($this->php_info["extensions_dir"] . "php_" . $name . ".dll")) {
            ConsoleOutput::success($name . ": extension installed")->print();
            $this->isExtensionLoaded($name);
        }

        ConsoleOutput::warning($name . ": extension not installed")->print()->break()->exit();
    }

    private function list(): never
    {
        ConsoleOutput::info("PECL Commands")->print()->break(true);
        ConsoleOutput::formattedRowData([
            "install" => "Installs the package in `ext` folder",
            "download" => "Download the package in the same directory",
            "status" => "Shows if extension is enabled",
            "info" => "Shows the extension description",
            "list" => "Shows all available commands",
            "version" => "Shows extension version"
        ], 15, true);

        exit;
    }

    private function version(string $extension_name): never
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

        exit;
    }

    private function selfUpdate(): never
    {
        $url = $this->repo_api_url . "releases";
        $json = file_get_contents($url, false);
        $tags = json_decode($json);
        $latest_tag = $tags[0]->tag_name;

        if (version_compare($latest_tag, self::VERSION, ">")) {
            ConsoleOutput::success("PECL component is updating...")->print()->break();

            $latest_pecl = $this->repo_url . "releases/download/" . $latest_tag . "/pecl.phar";
            $pecl_temp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "pecl-updated.phar";
            $file = fopen($latest_pecl, "r");
            $result = file_put_contents($pecl_temp, $file);

            if ($result == false) ConsoleOutput::error("Failed to update PECL component")->print()->exit();
            echo $this->generateAutoUpdateFile();
            exit;
        } else {
            ConsoleOutput::success("PECL component is updated!")->print()->break()->exit();
        }
    }

    private function info(string $extension_name): never
    {
        if (
            !extension_loaded($extension_name) &&
            file_exists($this->php_info["extensions_dir"] . "php_" . $extension_name . ".dll")
        ) {
            clearstatcache();
            ConsoleOutput::warning("You must enable `" . $extension_name . "` extension in `php.ini`")
                ->print()->exit();
        }

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

        exit;
    }

    private function about(): never
    {
        ConsoleOutput::info("PECL Component")->print()->break();
        ConsoleOutput::formattedRowData([
            "Version" => self::VERSION,
            "Repository" => $this->repo_url
        ], 15, true);

        exit;
    }

    private function generateAutoUpdateFile(): mixed
    {
        $data = '#!/usr/bin/env php
<?php

$current_pecl_file = str_replace("phar://", "", \'' . __DIR__ . '\');
$user_dir = dirname($current_pecl_file) . DIRECTORY_SEPARATOR;

$is_moved = rename(
    sys_get_temp_dir() . DIRECTORY_SEPARATOR . "pecl-updated.phar", 
    $user_dir . "pecl-updated.phar"
);

if ($is_moved == true && file_exists($user_dir . "pecl-updated.phar")) {
    clearstatcache();

    if (copy($user_dir . "pecl-updated.phar", $user_dir . "pecl.phar")) {
        unlink($user_dir . "pecl-updated.phar");
        echo "\033[92mPECL component updated with successfully!\e[0m \n";
        echo "Execute `php pecl.phar about` to see current version";
    } 
} else {
    echo "\033[41mError to update PECL component!\e[0m";
}';

        $pecl_autoupdate_file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "pecl-autoupdate-" . date("Y-m-d") . ".php";
        $result = file_put_contents($pecl_autoupdate_file, $data);
        if ($result != false) return shell_exec("php " . $pecl_autoupdate_file);
        return ConsoleOutput::error("Error to update PECL component!")->getMessage();
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
            $this->php_info["thread_safe"] = "ts";
        } else {
            $this->php_info["thread_safe"] = "nts";
        }

        $compiler = shell_exec("php -i | findstr Compiler");
        $compiler = explode("=>", trim($compiler));

        $this->php_info["compiler"] = trim($compiler[1]);
        $this->php_info["php_version"] = PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;
        $this->php_info["extensions_dir"] = $this->PhpExtensionDir();
        $this->php_info["OSBits"] = empty(strstr(php_uname("m"), "64")) ? "86" : "64";

        return $this->php_info;
    }

    private function isExtensionLoaded(string $extension_name): never
    {
        if (extension_loaded($extension_name) == true) {
            ConsoleOutput::success($extension_name . ": extension already installed and enabled")
                ->print()->break();
        } else {
            ConsoleOutput::success($extension_name . ": extension installed, but not enabled. Add the line below in the `php.ini` file to enable the extension")
                ->print()->break(true);
            $this->configIniComponent($extension_name);
        }

        exit;
    }

    private function configIniComponent(string $name): void
    {
        $url = $this->repo_url . "tree/main/extensions-required-files/";

        switch ($name) {
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
                ConsoleOutput::info("[" . $name . "]")->print()->break();
                ConsoleOutput::info("extension=" . $name)->print()->break(true);
                ConsoleOutput::success("To see the settings for this extension, run the command `php pecl.phar info " . $name . "`")
                    ->print();
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
}
