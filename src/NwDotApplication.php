<?php

namespace NwDot\Application;

use Exception;

use ArrayIterator;
use Iterator;
use IteratorAggregate;
use Traversable;

use NwDot\{

	Application\NwDotApplicationInterface as ApplicationInterface,
	Application\NwDotCallback as Callback,
	Application\NwDotCallbackInterface as CallbackInterface,
	Application\NwDotContainerInstanceResolver as ContainerInstanceResolver,
	Application\NwDotApplicationResponse as ApplicationResponse

};

use Psr\{

	Container\ContainerInterface

};

if ( ! class_exists( __NAMESPACE__ . '\\' . basename( __FILE__, '.php' ) ) ) {
class NwDotApplication implements IteratorAggregate, ApplicationInterface
{

	const APPLICATION = 'application';

	const RAWKEY_NAME = 0;
	const RAWKEY_CALLBACK = 1;
	const RAWKEY_ARGS = 2;
	const RAWKEY_CONTEXT = 3;

	const RAWKEY = [
		'name' => 'RAWKEY_NAME',
		'callback' => 'RAWKEY_CALLBACK',
		'args' => 'RAWKEY_ARGS',
		'context' => 'RAWKEY_CONTEXT'
	];

	public $container = [];
	public Iterator $iterator;

	public ContainerInterface $service;

	public $init = [];

	public $response = null;
	public $responseKey = null;

	public $defaultResolver = ContainerInstanceResolver::class;
	public $defaultReponse = ApplicationResponse::class;

	public static $exceptions = [];
	public static $debug = 0;

	public static $instance = [];

	public function __construct( ContainerInterface $container, array $callbacks = [] )
	{

		$this->setContainer( $container );
		$this->setCallbacks( $callbacks );

	}

	public function getIterator() : Traversable
	{

		if ( ! isset( $this->iterator ) ) {
			$this->iterator = new ArrayIterator( $this->getCallbacks() );
		}

		$iterator = $this->iterator;
		$iterator->rewind();

		return $iterator;

	}

	public function setContainer( ContainerInterface $container )
	{

		$this->service = $container;

	}

	public function getContainer()
	{

		return $this->service;

	}

	public function setCallbacks( $callbacks = [] )
	{

		if ( ! is_array( $callbacks ) ) {
			throw new Exception( 'Excpecting callbacks array' );
		}

		$callbacks = $this->toCallbacksArray( $callbacks );

		foreach ( $callbacks as $callback ) {
			$this->container[ $callback->getName() ] = $callback;
		}

	}

	public function getCallbacks()
	{

		return $this->container;

	}

	public function toCallbacksArray( array $raw )
	{

		$callbacksArray = [];

		foreach ( $raw as $key => $callback ) {
			if ( ! is_a( $callback, CallbackInterface::class ) ) {
				if ( is_array( $callback ) ) {
					$callback = $this->toCallback( ...array_values( $callback ) );
				} else {
					throw new Exception( 'Unknwon raw type, expecting array, received, %s', gettype( $callback ) );
				}
			}
			$callbacksArray[ $key ] = $callback;
		}

		return $callbacksArray;

	}

	public function toCallback( $name, $callback, $args = [], $context = null )
	{

		$callback = new Callback( ...func_get_args() );
		return $callback;

	}

	public function setDefaultResolver( CallbackResolver $resolver )
	{

		$this->defaultResolver = $resolver;

	}

	public function getDefaultResolver()
	{

		if ( isset( $this->defaultResovler ) ) {
			return $this->defaultResolver;
		}

	}

	public function hasDefaultResolver()
	{

		return isset( $this->defaultResolver );

	}

	public function setDefaultResponse( CallbackResponse $response )
	{

		$this->defaultResponse = $response;

	}

	public function getDefaultResponse()
	{

		if ( isset( $this->defaultResponse ) ) {
			return $this->defaultResponse;
		}

	}

	public function hasDefaultResponse()
	{

		return isset( $this->defaultResponse );

	}

	//////////

	// This is a callable object and can be added to any chain of callbacks

	public function __invoke()
	{

		return $this->invoke();

	}

	public function invoke()
	{

		$iterator = $this->getIterator();

		while( $iterator->valid() && ! isset( $this->response ) ) {

			$response = $this->call( $iterator->current() );

			$iterator->next();
		}

		if ( isset( $response ) ) {
			return $response;
		}

	}

	public function call( CallbackInterface $callback )
	{

		$callback = $this->resolve( $callback );

var_dump( $callback );


		$name = $callback->getName();

		$container = $this->getContainer();

		$args = array_values( $callback->getArgs() );
		$rps = $callback->getParameters();
		foreach ( $rps as $key => $parameter ) {
			$param = $parameter->getName();

			if ( ! isset( $args[ $key ] ) ) {
				$args[ $key ] = $container->get( $param );
			}
		}
		$callback->setArgs( $args );

		$response = $this->getResponse( $callback );

		if ( ! is_null( $response ) ) {
			$container->set( $name, $response );
		}

		if ( $name === $this->getResponseKey() ) {
			$this->response = $response;
		}

		return $response;

	}

	public function resolve( CallbackInterface $callback )
	{

		$callback->setContainer( $this->getContainer() );

		$resolver = $this->getResolver( $callback->getResolver() );
		if ( is_null( $resolver ) ) {
			return $callback;
		}

		$resolver->setCallback( $callback );

		$callback = $resolver->resolve( $callback );
		return $callback;

	}

	public function setResolver( $resolver )
	{

		$this->resolver = $resolver;

	}

	public function getResolver( $resolver = null )
	{

		$resolver = $this->orDefault( 'defaultResolver', $resolver );

		if ( is_null( $resolver ) ) {
			return;
		}

		if ( ! is_object( $resolver ) ) {
			$resolver = $this->inst( $resolver );
		}

		return $resolver;

	}

	public function getResponse( CallbackInterface $callback )
	{

		$result = $callback();

		$response = $this->orDefault( 'defaultResponse', $callback->getResponse() );
		if ( ! is_null( $response ) ) {
			$response = $response->respond( $this, $callback );
		} else {
			$response = $result;
		}

		return $response;

	}

	public function inst( $classOrObject )
	{

		$object = null;

		if ( is_string( $classOrObject ) && class_exists( $classOrObject ) ) {
			$object = new $classOrObject;
		}

		return $object;

	}

	public function with( $callbacks = null, $resolver = null, $response = null )
	{

		if ( ! is_null( $callbacks ) ) {
			$this->setCallbacks( $callbacks );
		}

		if ( ! is_null( $resolver ) ) {
			$this->setResolver( $resolver );
		}

		if ( ! is_null( $response ) ) {
			$this->setResponse( $response );
		}

		return $this;

	}

	public function withCallbacks( $callbacks, ...$callback ) {

		foreach ( $callback as $item ) {
			$callbacks[] = $item;
		}

		return $callbacks;

	}

	public function withCallback( Callback $obj, $callback = null, $args = null, $resolver = null, $response = null )
	{

		if ( ! is_null( $callable ) ) {
			$obj->setCallback( $callback );
		}
		if ( ! is_null( $args ) ) {
			$obj->setArgs( $args );
		}
		if ( ! is_null( $resolver ) ) {
			$obj->setResolver( $resolver);
		}
		if ( ! is_null( $response ) ) {
			$obj->setResponse( $response );
		}

		return $obj;

	}

	public function withCallbackArray( $callbackArray, $callback = null, $args = null, $resolver = null, $response = null )
	{

		$this->callbackArray = array_values( $callbackArray );

		$args = func_get_args();
		array_shift( $args );

		$rclass = new ReflectionMethod( $this, __FUNCTION__ );
		$rparams = $rclass->getParameters();

		foreach ( $rparams as $key => $rparam ) {
			$callbackArray[ $key ] = ${$rparam->getName()};
		}

		return $callbackArray;

	}

	public function orDefault( $property, $value = null )
	{

		if ( ! property_exists( $this, $property ) ) {
			return $value;
		}

		if ( is_null( $value ) ) {
			return isset( $this->$property ) ? $this->$property : null;
		}

		return $value;

	}

	public function setResponseKey( $responseKey )
	{

		$this->responseKey = $responseKey;

	}

	public function getResponseKey()
	{

		return $this->responseKey;

	}

	public static function exception( Exception $e, int $handle = null, $message = null )
	{

		static::$exceptions[] = $e;

		if ( ! isset( $handle ) ) {
			$handle = static::$debug;
		}

		$handle = (bool) $handle;

		if ( isset( $message ) ) {
			$e = new Exception( $message, $handle, $e );
		}

		switch ( $handle ) {

			case true:
				throw $e;
				break;

			case false:
				return false;
				break;
		}

	}

	public static function getInstance()
	{

		return new static( ...func_get_args() );

	}

}
}
