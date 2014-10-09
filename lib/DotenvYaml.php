<?php
namespace jimbocoder;

use Symfony\Component\Yaml\Parser;
use Doctrine\Common\Cache\FilesystemCache;

class DotenvYaml {

    const CACHE_TTL = 5;
    const CACHE_GRACE_TTL = 5;

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

        // Just to avoid a silly E_NOTICE:
        if ( !array_key_exists('.yml', $_ENV) ) {
            $_ENV['.yml'] = array();
        }

        // Also merge the values into the superglobal
        $_ENV['.yml'] = array_merge_recursive((array)$_ENV['.yml'], $rootNode);
    }

    /**
     * Convenience method to make reading the env values a little easier.
     */
    public static function get($dottedKey, $default = null)
    {
        // By forbidding these characters, I'm pretty sure eval() can be made safe.
        if ( strpbrk($dottedKey, '{};') ) {
            throw new \Exception("Unsafe key lookup!");
        }

        // Put together a string like $_ENV[".yml"]["a"]["b"]["c"]...
        $lookup = '$_ENV[".yml"]["' . str_replace('.', '"]["', $dottedKey) . '"]';

        // Do the lookup!
        if ( ($val = eval("if ( isset($lookup) ) { return $lookup; }")) !== null ) {
            return $val;
        } else {
            return $default;
        }
    }

    /**
     * Parse the file(s) from the filesystem
     * @param $envFile
     * @throws \InvalidArgumentException
     * @return array
     */
    protected static function _load($envFile, $conf_d)
    {
        // Try to get a fully parsed config tree from cache
        $cache = new FilesystemCache("$conf_d/.cache");
        $cacheKey = sprintf("%s:%s+%s", __CLASS__, $envFile, $conf_d);
        $cacheGraceKey = sprintf("%s:grace:%s+%s", __CLASS__, $envFile, $conf_d);
        if ( $parsedTree = $cache->fetch($cacheKey) ) {
            return $parsedTree;
        } else if ( $parsedTree = $cache->fetch($cacheGraceKey) ) {
            return $parsedTree;
        }

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
        $parsedTree = $parser->parse(file_get_contents($envFile));
        $cache->save($cacheKey, $parsedTree, self::CACHE_TTL);
        $cache->save($cacheGraceKey, $parsedTree, self::CACHE_TTL + self::CACHE_GRACE_TTL);
        return $parsedTree;
    }

    /**
     * recursively crawl the configuration tree and apply each leaf to the environment
     * @param array $root           Configuration array
     * @param callback $leafHandler Applied to each leaf node in the tree
     * @param string $prefix        Don't worry about it
     */
    protected static function _traverse($root, $leafHandler, $prefix='')
    {
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
    protected static function _chooseLeafHandler()
    {
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

