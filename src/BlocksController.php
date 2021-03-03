<?php

namespace AcfGutenbergBlocks;

class BlocksController {

    public function __construct() {
        // Check whether WordPress and ACF are available; bail if not.
        if(!function_exists('acf_register_block_type'))
            return;
        if(!function_exists('add_filter'))
            return;
        if(!function_exists('add_action'))
            return;

        add_filter('sage-acf-gutenberg-blocks-templates', array($this, 'setBlocksDirectories'));

        // Add the default blocks location, 'views/blocks', via filter
        add_filter('sage-acf-gutenberg-blocks-controllers', function() {
            return [
                'views/blocks',
            ];
        });

        add_action('acf/init', array($this, 'init'), 1);
    }

    public function init() {
        // Get an array of directories containing blocks
        $directories = apply_filters('sage-acf-gutenberg-blocks-controllers', []);

        // Check whether ACF exists before continuing
        foreach($directories as $directory):
            $dir = \App\isSage10() ? \Roots\resource_path($directory) : \locate_template($directory);

            // Sanity check whether the directory we're iterating over exists first
            if(!file_exists($dir))
                return;

            $directory = new \RecursiveDirectoryIterator($dir);
            $iterator = new \RecursiveIteratorIterator($directory);

            foreach($iterator as $path):
                if(!$this->isController($path))
                    continue;

                // Get namespace from file
                $namespace = $this->getNamespace($path->getPathname());
                // Strip the file extension to get the class name
                $className = $this->removeFileExtension($path->getFilename());

                // If there is no class name (most likely because the filename does
                // not end with ".php"), move on to the next file
                if(empty($className))
                    continue;

                // Include file
                require_once $path->getPathname();
                $class = !empty($namespace) ? "{$namespace}\\{$className}" : $className;

                // Instantiate controller
                if(class_exists($class))
                    new $class;
            endforeach;
        endforeach;
    }

    /**
     * removeFileExtension Strip the `.php` from a controller filename
     *
     * @param  mixed $filename The file name
     * @return string File name without extension
     */
    protected function removeFileExtension($fileName): string {
        // Filename must end with ".php". Parenthetical captures the slug
        $pattern = '/(.*)\.php$/';
        $matches = [];

        // If the filename matches the pattern, return the slug
        if(preg_match($pattern, $fileName, $matches))
            return $matches[1];

        // Return original name if the filename doesn't match the pattern
        return $fileName;
    }

    /**
     * isController
     *
     * @param  mixed $path
     * @return bool
     */
    protected function isController($path): bool {
        if(!$path->isFile() || $path->isFile() && $path->getExtension() != 'php')
            return false;

        return !strpos($path->getFileName(), '.blade');
    }

    /**
     * getNamespace Looks namespace defined in that file
     *
     * @param  mixed $file Path to file
     * @return string Namespace found in file
     */
    protected function getNamespace($file): string {
        $namespacePosition = array();
        $found = FALSE;
        $index = 0;

        if(!file_exists($file))
            return NULL;

        $er = error_reporting();
        error_reporting(E_ALL ^ E_NOTICE);

        $phpCode = file_get_contents($file);
        $tokens = token_get_all($phpCode);
        $count = count($tokens);

        for($i = 0; $i < $count; $i++):
            if(!$found && $tokens[$i][0] == T_NAMESPACE):
                $namespacePosition[$index]['start'] = $i;
                $found = TRUE;
            elseif($found && ($tokens[$i] == ';' || $tokens[$i] == '{')):
                $namespacePosition[$index]['end']= $i;
                $index++;
                $found = FALSE;
            endif;
        endfor;

        error_reporting($er);

        if(!empty($namespacePosition)):

            foreach($namespacePosition as $p):
                $namespace = '';

                for($i = $p['start'] + 1; $i < $p['end']; $i++)
                    $namespace .= $tokens[$i][1];

                return trim($namespace);
            endforeach;
        endif;

        return '';
    }

    /**
     * setBlocksDirectories
     *
     * @param  mixed $directories
     * @return array
     */
    public function setBlocksDirectories($directories): array {
        $newDirectories = array();

        foreach($directories as $directory):
            $dir = \App\isSage10() ? \Roots\resource_path($directory) : \locate_template($directory);

            $directoryIterator = new \RecursiveDirectoryIterator($dir);
            $iterator = new \RecursiveIteratorIterator($directoryIterator, \RecursiveIteratorIterator::SELF_FIRST);

            foreach($iterator as $path):
                if($path->isDir() && $path->getFileName() != '.' && $path->getFileName() != '..')
                    array_push($newDirectories, "{$directory}/{$path->getFileName()}");
            endforeach;
        endforeach;

        return array_merge($directories, $newDirectories);
    }

}

new BlocksController;
