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

        // Add the default blocks location, 'views/blocks', via filter
        add_filter('sage-acf-gutenberg-blocks-controllers', function() {
            return [
                'app/Blocks',
            ];
        });

        add_action('acf/init', array($this, 'init'));
    }

    public function init() {
        $themeDirectory = wp_get_theme();
        $themeDirectory = "{$themeDirectory->theme_root}/{$themeDirectory->stylesheet}";

        // Get an array of directories containing blocks
        $directories = apply_filters('sage-acf-gutenberg-blocks-controllers', []);

        // Check whether ACF exists before continuing
        foreach($directories as $directory):
            $dir = "{$themeDirectory}/{$directory}";

            // Sanity check whether the directory we're iterating over exists first
            if(!file_exists($dir))
                return;

            // Iterate over the directories provided and look for templates
            $controllersDirectory = new \DirectoryIterator($dir);

            foreach($controllersDirectory as $controller):
                if($controller->isDot() || $controller->isDir())
                    continue;

                // Get namespace from file
                $namespace = $this->getNamespace($controller->getPathname());
                // Strip the file extension to get the class name
                $className = $this->removeFileExtension($controller->getFilename());

                // If there is no class name (most likely because the filename does
                // not end with ".php"), move on to the next file
                if(empty($className))
                    continue;

                // Include file
                require_once $controller->getPathname();
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

}

new BlocksController;