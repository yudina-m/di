<?php
/**
 * The Orno Component Library
 *
 * @author  Phil Bennett @philipobenito
 * @license MIT (see the LICENSE file)
 */
namespace OrnoTest\Assets;

/**
 * FooWithDefaultArg
 */
class FooWithDefaultArg
{
    public $name;

    public function __construct($name = 'Phil Bennett')
    {
        $this->name = $name;
    }
}
