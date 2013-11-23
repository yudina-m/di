<?php
/**
 * The Orno Component Library
 *
 * @author  Phil Bennett @philipobenito
 * @license MIT (see the LICENSE file)
 */
namespace OrnoTest;

use Orno\Di\Container;
use Orno\Di\Definition\Factory;

/**
 * ContainerTest
 */
class ContainerTest extends \PHPUnit_Framework_Testcase
{
    public function testAutoResolvesNestedDependenciesWithAliasedInterface()
    {
        $c = new Container;

        $c->add('OrnoTest\Assets\BazInterface', 'OrnoTest\Assets\Baz');

        $foo = $c->get('OrnoTest\Assets\Foo');

        $this->assertInstanceOf('OrnoTest\Assets\Foo', $foo);
        $this->assertInstanceOf('OrnoTest\Assets\Bar', $foo->bar);
        $this->assertInstanceOf('OrnoTest\Assets\Baz', $foo->bar->baz);
        $this->assertInstanceOf('OrnoTest\Assets\BazInterface', $foo->bar->baz);
    }

    public function testInjectsArgumentsAndInvokesMethods()
    {
        $c = new Container;

        $c->add('OrnoTest\Assets\Bar')
          ->withArguments(['OrnoTest\Assets\Baz']);

        $c->add('OrnoTest\Assets\Baz');

        $c->add('OrnoTest\Assets\Foo')
          ->withArgument('OrnoTest\Assets\Bar')
          ->withMethodCall('injectBaz', ['OrnoTest\Assets\Baz']);

        $foo = $c->get('OrnoTest\Assets\Foo');

        $this->assertInstanceOf('OrnoTest\Assets\Foo', $foo);
        $this->assertInstanceOf('OrnoTest\Assets\Bar', $foo->bar);
        $this->assertInstanceOf('OrnoTest\Assets\Baz', $foo->baz);
    }

    public function testInjectsRuntimeArgumentsAndInvokesMethods()
    {
        $c = new Container;

        $c->add('OrnoTest\Assets\Bar')
          ->withArguments(['OrnoTest\Assets\Baz']);

        $c->add('OrnoTest\Assets\Baz');

        $c->add('OrnoTest\Assets\Foo')
          ->withArgument('OrnoTest\Assets\Bar')
          ->withMethodCalls(['injectBaz' => ['OrnoTest\Assets\Baz']]);

        $runtimeBar = new \OrnoTest\Assets\Bar(
            new \OrnoTest\Assets\Baz
        );

        $foo = $c->get('OrnoTest\Assets\Foo', [$runtimeBar]);

        $this->assertInstanceOf('OrnoTest\Assets\Foo', $foo);
        $this->assertInstanceOf('OrnoTest\Assets\Bar', $foo->bar);
        $this->assertInstanceOf('OrnoTest\Assets\Baz', $foo->baz);

        $this->assertSame($foo->bar, $runtimeBar);
    }

    public function testSingletonReturnsSameInstanceEverytime()
    {
        $c = new Container;

        $c->singleton('OrnoTest\Assets\Baz');

        $this->assertTrue($c->isSingleton('OrnoTest\Assets\Baz'));

        $baz1 = $c->get('OrnoTest\Assets\Baz');
        $baz2 = $c->get('OrnoTest\Assets\Baz');

        $this->assertTrue($c->isSingleton('OrnoTest\Assets\Baz'));
        $this->assertSame($baz1, $baz2);
    }

    public function testStoresAndReturnsArbitraryValues()
    {
        $baz1 = new \OrnoTest\Assets\Baz;
        $array1 = ['Phil', 'Bennett'];

        $c = new Container;

        $c->add('baz', $baz1);
        $baz2 = $c->get('baz');

        $c->add('array', $array1);
        $array2 = $c->get('array');

        $this->assertSame($baz1, $baz2);
        $this->assertSame($array1, $array2);
    }

    public function testReflectionOnNonClassThrowsException()
    {
        $this->setExpectedException('Orno\Di\Exception\ReflectionException');

        (new Container)->get('FakeClass');
    }

    public function testReflectionOnClassWithNoConstructorCreatesDefinition()
    {
        $c = new Container;

        $this->assertInstanceOf('OrnoTest\Assets\Baz', $c->get('OrnoTest\Assets\Baz'));
    }

    public function testReflectionInjectsDefaultValue()
    {
        $c = new Container;

        $this->assertSame('Phil Bennett', $c->get('OrnoTest\Assets\FooWithDefaultArg')->name);
    }

    public function testReflectionThrowsExceptionForArgumentWithNoDefaultValue()
    {
        $this->setExpectedException('Orno\Di\Exception\UnresolvableDependencyException');

        $c = new Container;

        $c->get('OrnoTest\Assets\FooWithNoDefaultArg');
    }

    public function testEnablingAndDisablingCachingWorksCorrectly()
    {
        $cache = $this->getMockBuilder('Orno\Cache\Cache')->disableOriginalConstructor()->getMock();

        $c = new Container(null, null, $cache);

        $this->assertTrue($c->isCaching());

        $c->disableCaching();

        $this->assertFalse($c->isCaching());

        $c->enableCaching();

        $this->assertTrue($c->isCaching());
    }

    public function testContainerSetsCacheWhenAvailableAndEnabled()
    {
        $cache = $this->getMockBuilder('Orno\Cache\Cache')
                      ->setMethods(['get', 'set'])
                      ->disableOriginalConstructor()
                      ->getMock();

        $cache->expects($this->once())
              ->method('set')
              ->with($this->equalTo('orno::container::OrnoTest\Assets\Baz'));

        $cache->expects($this->once())
              ->method('get')
              ->with($this->equalTo('orno::container::OrnoTest\Assets\Baz'))
              ->will($this->returnValue(false));

        $c = new Container(null, null, $cache);

        $this->assertInstanceOf('OrnoTest\Assets\Baz', $c->get('OrnoTest\Assets\Baz'));
    }

    public function testContainerGetsFromCacheWhenAvailableAndEnabled()
    {
        $cache = $this->getMockBuilder('Orno\Cache\Cache')
                      ->setMethods(['get', 'set'])
                      ->disableOriginalConstructor()
                      ->getMock();

        $definition = $this->getMockBuilder('Orno\Di\Definition\ClassDefinition')
                           ->disableOriginalConstructor()
                           ->getMock();

        $definition->expects($this->any())
                   ->method('__invoke')
                   ->will($this->returnValue(new Assets\Baz));

        $definition = serialize($definition);

        $cache->expects($this->once())
              ->method('get')
              ->with($this->equalTo('orno::container::OrnoTest\Assets\Baz'))
              ->will($this->returnValue($definition));

        $c = new Container(null, null, $cache);

        $this->assertInstanceOf('OrnoTest\Assets\Baz', $c->get('OrnoTest\Assets\Baz'));
    }
}
