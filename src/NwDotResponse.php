<?php

namespace NwDot\Application;

if ( ! class_exists( __NAMESPACE__ . '\\' . basename( __FILE__, '.php' ) ) ) {
class NwDotResponse
{

	public function response( Application $application, CallbackInterface $callback )
	{

		$response = $callback->getResponse();
		return $response;

	}

}
}
