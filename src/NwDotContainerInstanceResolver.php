<?php

namespace NwDot\Application;

if ( ! class_exists( __NAMESPACE__ . '\\' . basename( __FILE__, '.php' ) ) ) {
class NwDotContainerInstanceResolver
{

	public function __invoke()
	{

		return $this->resolve( ...func_get_args() );

	}

	public function resolve()
	{

		$container = $this->getContainer();
		$callback = $this->getCallback();

		$callable = $callback->getCallback();
		if ( is_array( $callable ) && $container->has( $callable[0] ) ) ) {
			$callable[0] = $container->get( $callable[0] );
		}

		$callback->setCallback( $callable );

		return $callback;

	}

	public function setClass( $class )
	{

		if ( ! class_exists( $class ) ) {
			throw new Exception( 'Class must exist' );
		}

		$this->class = $class;

		return $this;

	}

	public function getClass( $class )
	{

		$this->class = $class;

	}

	public function setCallback( $callback )
	{

		$this->callback = $callback;

		return $this;

	}

	public function getCallback()
	{

		return $this->callback;

	}

	public function getContainer()
	{

		return $this->getCallback()->getContainer();

	}

}
}
