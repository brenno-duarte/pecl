<?php

class BuildPhar
{
    /**
     * @param string $source_directory   // This is the directory where your project is stored.
     * @param string $stub_file          // Name the entry point for your phar file. This file 
     *                                      have to be within the source directory. 
     * @param string  $output_directory  // Directory where the phar file will be placed.
     * @param string $phar_filename      // Name of your final *.phar file.
     */
    public static function build(
        string $source_directory,
        string $stub_file = 'index.php',
        string $output_directory = '',
        string $phar_filename = 'app.phar'
    ): true
    {
        try {
            $phar = new Phar($output_directory . DIRECTORY_SEPARATOR . $phar_filename);
            $phar->buildFromDirectory($source_directory);
            $phar->setDefaultStub($stub_file);

            return true;
        } catch (Exception $e) {
            die('Error: ' . $e->getMessage());
        }
    }

    public static function compress(
        string $source_directory,
        string $output_directory = '',
        string $phar_filename = 'compress.phar'
    ): true
    {
        try {
            $phar = new Phar($output_directory . DIRECTORY_SEPARATOR . $phar_filename);
            $phar->buildFromDirectory($source_directory);
            //$phar->setDefaultStub($stub_file);

            return true;
        } catch (Exception $e) {
            die('Error: ' . $e->getMessage());
        }
    }

    public static function extract(string $phar_file, string $dir_extract): true
    {
        try {
            $phar = new Phar($phar_file);
            $phar->extractTo($dir_extract);

            return true;
        } catch (Exception $e) {
            die('Error: ' . $e->getMessage());
        }
    }
}

//var_dump(BuildPhar::build('files', 'index.php', __DIR__ , 'app.phar'));
var_dump(BuildPhar::compress('files', __DIR__ , 'pecl.phar'));
//var_dump(BuildPhar::extract('app.phar', __DIR__ . DIRECTORY_SEPARATOR . 'extracted'));