<?php

//var_dump(extension_loaded("zip"));exit;

$php_info_test = [
    "thread_safe" => "nts",
    "php_version" => "8.3",
    "OSBits" => empty(strstr(php_uname("m"), '64')) ? "86" : "64"
];

$repo = "krakjoe/apcu";
//$link_api = "https://api.github.com/repos/" . $repo . "/releases";
$link_api = "https://api.github.com/repos/";
$link_content = "https://raw.githubusercontent.com/" . $repo . "/releases";

$packages = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . "packages.json");
$packages = json_decode($packages, true);

if (isset($packages["apcu"])) {
    $json = file_get_contents($link_api . $packages["repo"] . "/releases", false);
    $result = json_decode($json);
    $assets_files = $result[0]->assets;
    $file = null;
    $link_download = null;

    foreach ($assets_files as $key => $value) {
        if (
            str_contains($value->name, $php_info_test["thread_safe"]) &&
            str_contains($value->name, $php_info_test["php_version"]) &&
            str_contains($value->name, $php_info_test["OSBits"])
        ) {
            //$file = $link_content . "/download/" . $value->name;
            $link_download = $value->browser_download_url;
            $file = $value->name;
        }

        //var_dump($value);
    }

    $result = file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . $file, fopen($link_download, "r"));

    if (pathinfo(__DIR__ . DIRECTORY_SEPARATOR . "apcu-8.3-x64-nts.zip", PATHINFO_EXTENSION) == "zip") {
        $zip = new ZipArchive();

        if ($zip->open(__DIR__ . DIRECTORY_SEPARATOR . "apcu-8.3-x64-nts.zip") == true) {
            $zip->extractTo(__DIR__ . DIRECTORY_SEPARATOR . "apcu-8.3-x64-nts");
            $zip->close();

            $result = copy(
                __DIR__ . DIRECTORY_SEPARATOR . "apcu-8.3-x64-nts" . DIRECTORY_SEPARATOR . "php_apcu.dll",
                __DIR__ . DIRECTORY_SEPARATOR . "php_apcu.dll"
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
