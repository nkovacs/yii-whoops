<?php
use Whoops\Run as Whoops;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\PrettyPageHandler;

class WhoopsErrorHandler extends CErrorHandler {

	/**
	 * Whoops instance.
	 * @var Whoops
	 */
	protected $whoops;

	/**
	 * Page title in case of non-AJAX requests.
	 * If not set, will use Whoops default: "Whoops! There was an error."
	 * @var string
	 */
	public $pageTitle;

	/**
	 * Editor to open files in. Can be one of the following:
	 *     sublime
	 *     emacs
	 *     textmate
	 *     macvim
	 *     xdebug
	 * or a protocol url with the tokens {file} and {line},
	 * which will be replaced by the filename and line number.
	 * {@link https://github.com/filp/whoops/blob/master/docs/Open%20Files%20In%20An%20Editor.md}
	 * @var string
	 */
	public $editor;

	protected $defaultDisabledLogRoutes = array('YiiDebugToolbarRoute');

	protected $disabledLogRoutes = array();

	/**
	 * Instantiate Whoops with the correct handlers.
	 */
	public function init() {
		parent::init();
		$this->whoops = new Whoops;

		if (Yii::app()->request->isAjaxRequest) {
			$this->whoops->pushHandler(new JsonResponseHandler);
		}
		else {
			$page_handler = new PrettyPageHandler;
			if (isset($this->pageTitle)) {
				$page_handler->setPageTitle($this->pageTitle);
			}
			if (isset($this->editor)) {
				$editor = $this->editor;
				switch ($editor) {
					case 'sublime':
					case 'emacs':
					case 'textmate':
					case 'macvim':
					case 'xdebug':
						$page_handler->setEditor($editor);
						break;
					default:
						$page_handler->setEditor(function ($file, $line) use ($editor) {
							return strtr($editor, array('{file}' => $file, '{line}' => $line));
						});
						break;
				}
			}
			$this->whoops->pushHandler($page_handler);
		}
	}

	/**
	 * Disables some log routes that would output stuff whenever the script finishes, trashing Whoops screen.
	 * @return true
	 */
	protected function disableLogRoutes() {
		//This part verifies if the log routes to disable really exists. If none, simply returns
		$disabled_routes = array_merge($this->defaultDisabledLogRoutes, $this->disabledLogRoutes);
		$continue        = false;
		foreach ($disabled_routes as $route) {
			if (class_exists($route, false)) {
				$continue = true;
				break;
			}
		}
		if (!$continue) return true;

		//Here we actually disable the given routes...
		$total = sizeof(Yii::app()->log->routes);
		for ($i = 0; $i < $total; $i++) {
			foreach ($disabled_routes as $route) {
				if (Yii::app()->log->routes[$i] instanceof $route) {
					Yii::app()->log->routes[$i]->enabled = false;
				}
			}
		}

		return true;
	}

	/**
	 * Forwards an error to Whoops.
	 * @param CErrorEvent $event
	 */
	protected function handleError($event) {
		if (!YII_DEBUG) {
			parent::handleError($event);
			return;
		}
		$this->disableLogRoutes();
		try {
			$this->whoops->handleError($event->code, $event->message, $event->file, $event->line);
		} catch (Exception $ex) {
			$this->handleException($ex);
		}
	}

	/**
	 * Forwards an exception to Whoops.
	 * @param Exception $exception
	 */
	protected function handleException($exception) {
		if ($exception instanceof CHttpException && $this->errorAction!==null || !YII_DEBUG) {
			parent::handleException($exception);
			return;
		}
		$this->disableLogRoutes();
		$this->whoops->handleException($exception);
	}

}
