<?php

namespace NwDot\Application;

use ArrayObject;

use NwDot\{

	Application\NwDotApplicationInterface as ApplicationInterface,
	Application\NwDotResponseInterface as Response

};

use Psr\{

	Container\ContainerInterface

};

if ( ! class_exists( __NAMESPACE__ . '\\' . basename( __FILE__, '.php' ) ) ) {
class NwDotApplication extends ArrayObject implements ApplicationInterface
{

	public $container = [];

	public $init = [];
	public $response = null;

	public $defaultResolver = null;
	public $defaultReponse = null;

	public static $exceptions = [];
	public static $debug = 0;

	public static $instance = [];

	public function __construct( ContainerInterface $container, $init = [] )
	{

		$this->initialize = $this->initialize( ...func_get_args() );

	}

	protected function initialize( $inits = [] )
	{

		static::$instance[] = $this;

		$this->init = new static;
		foreach ( $inits as $init ) {
			if ( isset( $init[0][0] ) && is_a( $init[0][0], static::class ) ) {
				$init[0][0] = $this;
			}
		}

		$this->init->setCallbacks( $inits );
		$this->init->invoke();

		$this->setCallbacks( new Callback( 'init', $this->init ) );

	}

	public function setCallbacks( CallbackInterface ...$callbacks )
	{

		$this->callbacks = $this->callbacks + $callbacks;

	}

	public function toCallbacksArray( array ...$callbacks )
	{

		foreach ( $callbacks as $key => $callback ) {
			$callbacks[ $key ] = $obj = new Callback( ...array_values( $callback ) );
		}

		return $callbacksArray;

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

		while( $iterator->isValid() && ! isset( $this->response ) ) {

			$response = $this->call( $iterator->current() );
		}

		return $response;

	}

	public function call( CallbackInterface $callback )
	{

		$callback = $this->resolve( $callback );
		$name = $callback->getName();

		$container = $this->getContainer();

		$args = [];
		$rps = $callback->getParameters();
		foreach ( $rps as $parameter ) {
			$param = $parameter->getName();

			$args[ $param ] = $container->get( $param );
		}
		$callback->setArgs( $args );

		$response = $this->getResponse( $callback );
		$container->set( $name, $response );

		if ( $name !== $this->getResponseKey() ) {
			$this->response = $response;
		}

		return $response;

	}

	public function resolve( CallbackInterface $callback )
	{

		$callback->setContainer( $callback );

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
