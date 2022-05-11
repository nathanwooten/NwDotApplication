<?php

namespace NwDot\Application;

if ( ! interface_exists( __NAMESPACE__ . '\\' . basename( __FILE__ . '.php' ) ) ) {
interface NwDotCallbackInterface
{

	public function __invoke();

}
}
