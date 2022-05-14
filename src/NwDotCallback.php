<?php

namespace NwDot\Application;

use Exception;

use NwDot\{

	Application\NwDotCallbackInterface as CallbackInterface

};

use Psr\{

	Container\ContainerInterface

};

if ( ! class_exists( __NAMESPACE__ . '\\' . basename( __FILE__, '.php' ) ) ) {
class NwDotCallback implements CallbackInterface
{

	const TYPE_CALLBACK = 0;
	const TYPE_ARGS = 1;
	const TYPE_CONTEXT = 2;

	const ARGS = 'Args';
	const CALLBACK = 'Callback';
	const CALLBACKS = 'Callbacks';
	const CONTEXT = 'Context';
	const NAME = 'Name';
	const RESOLVER = 'Resolver';

	public $callback = null;
	public $args = null;
	public array $context = [];

	public $name = [];

	public $resolver = null;
	public $response = null;

	public $rcallback = null;
	public $rparameters = [];

	public function __construct( $name = null, $callback = null, $args = null, $context = null )
	{

		if ( ! is_null( $name ) ) {
			$this->setName( $name );
		}

		if ( ! is_null( $callback ) ) {
			$this->setCallback( $callback );
		}
		if ( ! is_null( $args ) ) {
			$this->setArgs( $args );
		}
		if ( ! is_null( $context ) ) {
			$this->setContext( $context );
		}


	}

	public function __invoke()
	{

		$callback = $this->getCallback();
		$args = $this->getArgs();

		$result = $callback( ...$args );

		$this->response = $result;
		return $this->response;

	}

	public function setContainer( ContainerInterface $container )
	{

		$this->container = $container;

	}

	public function getContainer()
	{

		if ( isset( $this->container ) ) {
			return $this->container;
		}

	}

	public function setCallback( $callback )
	{

		$this->callback = $callback;

	}

	public function getCallback()
	{

		return $this->callback;

	}

	public function setArgs( $args )
	{

		$this->args = $args;

	}

	public function getArgs()
	{

		return isset( $this->args ) ? $this->args : [];

	}

	public function setContext( $context )
	{

		if ( is_null( $context ) ) $context = [];

		$this->context = $context;

	}

	public function getContext()
	{

		return $this->context;

	}

	public function setResponse( $response = null )
	{

		$this->response = $response;

	}

	public function getResponse()
	{

		return $this->response;

	}

	public function setResolver( $resolver = null )
	{

		$this->resolver = $resolver;

	}

	public function getResolver()
	{

		if ( isset( $this->resolver ) ) {
			return $this->resolver;
		}

	}

	public function setName( $name )
	{

		$this->name[] = $name;

	}

	public function getName( $array = false )
	{

		if ( $array ) {
			return $this->name;
		}

		return current( $this->name );

	}

	public function getMethod()
	{

		if ( is_null( $this->rcallback ) ) {

			$callable = $callback->getCallback();
			if ( is_array( $callable ) ) {
				$type = 'ReflectionMethod';
			} else {
				$type = 'ReflectionFunction';
			}

			$this->rcallback = new $type( ...$args );
		}

		return $this->rcallback;

	}

	public function getParameters()
	{

		if ( ! isset( $this->rparameters ) ) {

			$method = $this->getMethod();
			$this->rparameters = $method->getParameters();
		}

		return $this->rparameters;

	}

}
}
