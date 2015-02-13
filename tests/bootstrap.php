<?PHP

function bootstrapAutoloader($class) {
	
	$parts = explode('\\', $class);
	if ($parts[0] != 'Firelit') 
		return false;
		
	if (file_exists('lib'. DIRECTORY_SEPARATOR . $parts[0] . DIRECTORY_SEPARATOR . $parts[1] .'.php'))
		require_once('lib'. DIRECTORY_SEPARATOR . $parts[0] . DIRECTORY_SEPARATOR . $parts[1] .'.php');
}

spl_autoload_register('bootstrapAutoloader');
