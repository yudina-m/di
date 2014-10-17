<?php
/**
 * The Orno Component Library
 *
 * @author  Phil Bennett @philipobenito
 * @license MIT (see the LICENSE file)
 */
namespace Orno\Di\Exception;

/**
 * Thrown when a user tries to end a a service that is not defined.
 */
class ServiceNotRegisteredException extends \InvalidArgumentException
{

}
