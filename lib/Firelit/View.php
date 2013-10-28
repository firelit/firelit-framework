<?php 

namespace Firelit;

class View {
	
	static public $viewFolder = 'views/';
	static public $assetDirectory = '/assets/';

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

	protected function addAsset($name, $attributes = array()) {
		$nameArray = explode('.', $name);
		$ext = array_pop($nameArray);

		switch ($ext) {
			case 'js': 
				echo '<script type="text/javascript" src="'. $this->html(self::$assetDirectory . $name) .'"></script>'."\n";
				break;
			case 'css':
				if (!isset($attributes['rel'])) $attributes['rel'] = 'stylesheet';

				echo '<link href="'. $this->html(self::$assetDirectory . $name) .'"'; 
				foreach ($attributes as $name => $value) echo ' '. $name .'="'. $this->html($value) .'"';
				echo ">\n";

				break;
			case 'ico':

				echo '<link href="'. $this->html(self::$assetDirectory . $name) .'"'; 
				foreach ($attributes as $name => $value) echo ' '. $name .'="'. $this->html($value) .'"';
				echo ">\n";

				break;
		}

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
		if (preg_match('/\.php$/', $name)) $ext = ''; else $ext = '.php'; 
		$file = static::$viewFolder . $name . $ext;

		if (!file_exists($file)) throw new \Exception('View file does not exist: '. $name);
		if (!is_readable($file)) throw new \Exception('View file not readable: '. $name);

		return $file;
	}

	static public function quickRender($template, $layout = false, $data = false) {
		$class = get_called_class();
		$view = new $class($template, $layout);
		$view->render($data);
		return $view;
	}
}