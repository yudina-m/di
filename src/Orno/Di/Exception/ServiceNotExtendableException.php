<?php
/**
 * The Orno Component Library
 *
 * @author  Phil Bennett @philipobenito
 * @license MIT (see the LICENSE file)
 */
namespace Orno\Di\Exception;

/**
 * Thrown when a user tries to extend a service registered as a singleton and
 * the singleton has already been created.
 */
class ServiceNotExtendableException extends \RuntimeException
{

}
