<?php

class YiiWhoopsRunner extends \Whoops\Run
{

	protected function getInspector(Exception $exception)
	{
		require 'YiiWhoopsInspector.php';
		return new YiiWhoopsInspector($exception);
	}

}
