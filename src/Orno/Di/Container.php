<?php
/**
 * The Orno Component Library
 *
 * @author  Phil Bennett @philipobenito
 * @license MIT (see the LICENSE file)
 */
namespace Orno\Di;

use Orno\Config\Repository as Config;
use Orno\Cache\Cache;

/**
 * Container
 */
class Container implements ContainerInterface, \ArrayAccess
{
    /**
     * @var \Orno\Di\Definition\Factory
     */
    protected $factory;

    /**
     * @var \Orno\Config\Repository
     */
    protected $config;

    /**
     * @var \Orno\Cache\Cache
     */
    protected $cache;

    /**
     * @var array
     */
    protected $items = [];

    /**
     * @var array
     */
    protected $singletons = [];

    /**
     * Constructor
     *
     * @param \Orno\Di\Definition\Factory $factory
     * @param \Orno\Config\Repository     $config
     * @param \Orno\Cache\Cache           $cache
     */
    public function __construct(
        Definition\Factory $factory,
        Config $config = null,
        Cache $cache = null
    ) {
        $this->factory = $factory;
        $this->config  = $config;
        $this->cache   = $cache;

        $this->addItemsFromConfig();
    }

    /**
     * {@inheritdoc}
     */
    public function add($alias, $concrete = null, $singleton = false)
    {
        if (is_null($concrete)) {
            $concrete = $alias;
        }

        $this->items[$alias]['singleton'] = $singleton;

        if ($concrete instanceof \Closure) {
            $definition = $factory->closureDefinition($alias, $concrete, $this);
            $this->items[$alias]['definition'] = $definition;
            return $definition;
        }

        if (is_string($concrete) && class_exists($concrete)) {
            $definition = $factory->classDefinition($alias, $concrete, $this);
            $this->items[$alias]['definition'] = $definition;
            return $definition;
        }

        // if the concrete is an already instantiated object, we just store it
        // as a singleton
        if (is_object($concrete)) {
            $this->singletons[$alias] = $concrete;
        }

        // if we've got this far we're dealing with an arbitrary value
        $definition = $this->factory->arbitraryDefinition($alias, $concrete, $this);
        $this->items[$alias]['definition'] = $definition;

        return $definition;
    }

    /**
     * {@inheritdoc}
     */
    public function get($alias, array $args = [])
    {
        // if we have a singleton just return it
        if (array_key_exists($alias, $this->singletons)) {
            return $this->singletons[$alias];
        }

        // invoke the correct definition
        if (array_key_exists($alias, $this->items)) {
            $definition = $this->items[$alias]['definition'];

            if (
                $definition instanceof Definition\ClosureDefinition ||
                $definition instanceof Definition\ClassDefinition
            ) {
                return $definition($args);
            }

            return $definition();
        }

        // check for and invoke a definition that was reflected on then cached
        if (! is_null($this->cache)) {
            $cached = $this->cache->get('orno::container::' . $alias);

            if ($cached !== false) {
                $definition = unserialize($cached);
                return $definition();
            }
        }

        // if we've got this far, we can assume we need to reflect on a class
        // and automatically resolve it's dependencies, we we also cache the
        // result if a caching adapter is available
        $definition = $this->reflect($alias);
        $this->cache->set('orno::container::' . $alias, serialize($definition));
        $this->items[$alias]['definition'] = $definition;

        return $definition();
    }

    /**
     * {@inheritdoc}
     */
    public function isRegistered($alias)
    {
        return array_key_exists($alias, $this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function isSingleton($alias)
    {
        return (
            array_key_exists($alias, $this->singletons) ||
            (array_key_exists($alias, $this->items) && true === $this->items[$alias]['singleton'])
        );
    }

    /**
     * Populate the container with items from config
     *
     * @return void
     */
    protected function addItemsFromConfig()
    {
        array_walk($this->config->get('di', []), function (&$options, $alias) {
            $singleton = (array_key_exists('singleton', $options)) ?: false;
            $concrete  = (array_key_exists('class', $options)) ? $options['class'] : null;

            // if the concrete doesn't have a class associated with it then it
            // must be either a Closure or arbitrary type so we just bind that
            $concrete = (is_null($concrete)) ? $options : $concrete;

            $definition = $this->add($alias, $concrete, $singleton);

            // set constructor argument injections
            if (array_key_exists('arguments', $options)) {
                $definition->withArguments((array) $options['arguments']);
            }

            // set method calls
            if (array_key_exists('methods', $options)) {
                $definition->withMethodCalls((array) $options['methods']);
            }
        });
    }

    /**
     * Reflect on a class, establish it's dependencies and build a definition
     * from that information
     *
     * @param  string $class
     * @return \Orno\Di\Definition\ClassDefinition
     */
    protected function reflect($class)
    {
        try {
            $reflection  = new \ReflectionClass($class);
            $constructor = $reflection->getConstructor();
        } catch (\ReflectionException $e) {
            throw new Exception\ReflectionException(
                sprintf('Unable to reflect on the class [%s], does the class exist and is it properly autoloaded?', $class)
            );
        }

        $definition = $this->factory->classDefinition($class, $class, $this);

        if (is_null($constructor)) {
            return $definition;
        }

        // loop through dependencies and get aliases/values
        foreach ($constructor->getParameters() as $param) {
            $dependency = $param->getClass();

            // if the dependency is not a class we attempt to get a dafult value
            if (is_null($dependency)) {
                if ($param->isDefaultValueAvailable()) {
                    $definition->withArgument($param->getDafultValue());
                    continue;
                }

                throw new Exception\UnresolvableDependencyException(
                    sprintf('Unable to resolve a non-class dependency of [%s] for [%s]', $param, $class)
                );
            }

            // if the dependency is a class, just register it's name as an
            // argument with the definition
            $definition->withArgument($name);
        }

        return $definition;
    }
}
