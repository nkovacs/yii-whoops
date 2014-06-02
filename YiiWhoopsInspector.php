<?php

use Whoops\Exception\FrameCollection;

class YiiWhoopsInspector extends \Whoops\Exception\Inspector
{
	/**
	 * @var \Whoops\Exception\FrameCollection
	 */
	private $frames;

	/**
	 * Override default implementation for two reasons:
	 * ErrorException is caught by WhoopsErrorHandler, so it will
	 * have a line and would be displayed by Whoops.
	 * The default behavior of Whoops in case of an ErrorException
	 * without a line is to only display one frame, we instead display
	 * a full stack trace up to the actual error.
	 * The stack trace is cut off at CApplication::handleError.
	 * @return \Whoops\Exception\FrameCollection
	 */
	public function getFrames()
	{
		if($this->frames === null) {
			$exception = $this->getException();
			if (!$exception instanceof \Whoops\Exception\ErrorException) {
				$this->frames = parent::getFrames();
				return $this->frames;
			}

			$frames = $exception->getTrace();
			foreach ($frames as $i => $frame) {
				unset($frames[$i]);
				if ($frame['class'] === 'CApplication' && $frame['function'] === 'handleError') {
					break;
				}
			}
			$frames = array_values($frames);
			$this->frames = new FrameCollection($frames);
			// ErrorException doesn't have previous exception,
			// skip adding its frames
		}

		return $this->frames;
	}

}
