<?php
/**
 * The Orno Component Library
 *
 * @author  Phil Bennett @philipobenito
 * @license MIT (see the LICENSE file)
 */
namespace OrnoTest\Assets;

/**
 * Foo
 */
class Foo
{
    public $bar;

    public $baz;

    public function __construct(Bar $bar)
    {
        $this->bar = $bar;
    }

    public function injectBaz(BazInterface $baz)
    {
        $this->baz = $baz;
    }
}
