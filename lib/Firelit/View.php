<?php 

namespace Firelit;

class View {
	
	static public $viewFolder = 'views/';
	public $layout = false, $template = false;
	public $data = array();

	public function __construct($template = false, $layout = false) {
		$this->setLayout($layout);
		$this->setTemplate($template);
	}

	public function setLayout($layout) {
		$this->layout = $layout;
		return $this;
	}

	public function setTemplate($template) {
		$this->template = $template;
		return $this;
	}

	public function setData($data) {
		if (is_array($data)) $this->data = $data;
		return $this;
	}

	protected function yieldNow() {
		if (!$this->template) return;

		extract($this->data, EXTR_SKIP);

		$file = $this->fileName($this->template);
		include($file);
	}

	protected function html($html) {
		return htmlentities($html);
	}

	protected function includePart($name) {
		extract($this->data, EXTR_SKIP);

		$file = $this->fileName($name);
		include($file);
	}

	public function render($data = false) {
		$this->setData($data);
		extract($this->data, EXTR_SKIP);

		if ($this->layout) {

			$file = $this->fileName($this->layout);
			include($file);

		} else $this->yieldNow();

	}

	protected function fileName($name) {
		$file = static::$viewFolder . $name .'.php';
		if (!file_exists($file)) throw new Exception('View file does not exist.');
		if (!is_readable($file)) throw new Exception('View file not readable.');
		return $file;
	}

	static public function quickRender($template, $layout = false, $data = false) {
		$class = get_called_class();
		$view = new $class($template, $layout);
		$view->render($data);
		return $view;
	}
}