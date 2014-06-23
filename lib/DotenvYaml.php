<?php
namespace jimbocoder;

use Symfony\Component\Yaml\Parser;

class DotenvYaml {

    /**
     * Inject a configuration file + directory into the environment
     */
    public static function load($path, $file='.env.yml', $conf_d='.env.yml.d')
    {
        $envFile = "$path/$file";
        if ( substr($conf_d,0,1) === '/' ) {
            $conf_d = realpath($conf_d);
        } else {
            $conf_d = realpath(dirname($envFile) . '/' . $conf_d);
        }
        $rootNode = self::_load($envFile, $conf_d);

        // We choose the handler strategy up front, so we only have to make the decision once
        // instead of once per leaf.
        $leafHandler = self::_chooseLeafHandler();

        // Traverse the configuration tree and convert every leaf to the dotted syntax,
        // then add the dotted keys to the environment
        self::_traverse($rootNode, $leafHandler);

        // Also merge the values into the superglobal
        $_ENV['.yml'] = array_merge_recursive((array)$_ENV['.yml'], $rootNode);
    }

    /**
     * Parse the file(s) from the filesystem
     * @param $envFile
     * @throws \InvalidArgumentException
     * @return array
     */
    protected static function _load($envFile, $conf_d)
    {
        $envFile = realpath($envFile);
        if ( !file_exists($envFile) ) {
            throw new \InvalidArgumentException("Environment file `$envFile` not found.");
        }

        $parser = new Parser();
        $preFiles = glob("$conf_d/??-*.yml");
        array_map(function($f) use($parser) {
            $parser->parse(file_get_contents($f));
        }, $preFiles);

        $parsedEnvironment = $parser->parse(file_get_contents($envFile));
        return $parser->parse(file_get_contents($envFile));
    }


    /**
     * recursively crawl the configuration tree and apply each leaf to the environment
     * @param array $root           Configuration array
     * @param callback $leafHandler Applied to each leaf node in the tree
     * @param string $prefix        Don't worry about it
     */
    protected static function _traverse($root, $leafHandler, $prefix='') {
        foreach($root as $index=>$node) {
            $key = $prefix ? "$prefix.$index" : $index;

            // Handle leaf nodes directly, otherwise recurse deeper
            if (!is_array($node)) {
                $leafHandler($key, $node);
            } else {
                self::_traverse($node, $leafHandler, $key);
            }
        }
    }


    /**
     * Detect which env facilities are available, and return a function that knows how to use them
     */
    protected static function _chooseLeafHandler() {
        if ( function_exists('apache_setenv') ) {
            return function ($k, $v) {
                putenv("$k=$v");
                apache_setenv($k, $v);
            };
        } else {
            return function($k, $v) {
                putenv("$k=$v");
            };
        }
    }

}

