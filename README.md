Firelit-Framework
===============

Firelit's standard PHP framework provides a set of helpful classes for developing a website. They are created and namespaced so that they can easily be used with an auto-loader, following the [PSR-0 standard](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md).

**Not production ready!**

**NOTE: This project is a WIP and is not versioned because it is not yet complete. All tests have not yet been written and thus all classes have not yet been tested.**

TODO: UPDATE!

Requirements
------------

- PHP version 5.4.0 and higher
- MultiByte PHP extension
- Mcrypt PHP extension (required for `Crypto` class)
- cURL PHP extension (required for `HttpRequest` class)
- PDO PHP extension (required for `Query` class)
- SQLite PHP extension (required for `Query` class unit tests)

How to Use
----------

The easiest way to use this library is to use [Composer](http://getcomposer.org/) which automatically handles dependencies and auto-loading.

Here is an example `composer.json` that you could add to your project root:
```js
{
    "name": "acme/blog", /* Your package name */
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/firelit/firelit-library"
        }
    ],
    "require": {
        "firelit/library": "dev-master" /* It would be better to specify a version here */
    }
}
```

Alternatively, you could go the manual way and setup your own autoloader and copy the project files from `lib/` into your project directory.

Classes Included
----------------

### ApiResponse

A response-handling class for API end-points. Can handle all HTTP response codes and JSON & _limited_ XML. Set a template to ensure some fields are always sent back with the response.

Example usage:
```php
<?php

$resp = ApiResponse::init('JSON');

$resp->setTemplate(array(
	'success' => false,
	'message' => ''
));

$resp->code(404);

$resp->respondAndEnd(array(
	'message' => 'Resource could not be located.'
));
```

### Cache

A caching class. First uses php-memory cache (a global PHP variable) and configurable to use memcached second. The static variables `$cacheHit` and `$cacheMiss` are set after each cache check.

Example usage:
```php
<?php

Firelit\Cache::config(array(
	'memcached' => array(
		'enabled' => true,
		'servers' => array(
			array('cache.example.com', 11211, 33)
		)
	)
));

$val = Firelit\Cache::get('randomValue', function() {

	// If cache miss, this closure will retrieve and return the value
	return mt_rand(0, 1000);
	
});

if (Firelit\Cache::$cacheHit) 
	echo 'Cache hit!';
```

### CheckHTTPS

A short class to help verify a connection is secure (ie, using TLS) and take action (ie, redirect or return error) if it isn't. It would be best if this were done via the web server settings but this isn't always possible.

Example usage:
```php
<?php

// Simple redirect if the connection isn't secure
Firelit\CheckHTTPS::redirect();

// Or log the insecure access attempt and exit with HTTP 400
Firelit\CheckHTTPS::error(function() {
	new Firelit\LogEntry(1, 'Connection is not secure and it needs to be.', __FILE__, __LINE__);
});
```

### Crypto

A symmetrical-key encryption/decryption helper class (uses MCRYPT_RIJNDAEL_256 aka AES). Includes password hashing method.

Example encryption/decryption usage:
```php
<?php

$mySecretPassword = 'Super secret!';

$iv = Firelit\Crypto::getIv();
$encrypted = Firelit\Crypto::encrypt('Super secret text', $mySecretPassword, $iv);

$decrypted = Firelit\Crypto::decrypt($encrypted, $mySecretPassword, $iv);
```

Example password hasing usage:
```php
<?php

// Getting a hash for a new password
list($hash, $salt) = Firelit\Crypto::password($_POST['new_password']);

// Checking a submitted password against a user's stored info
list($hash, $salt) = Firelit\Crypto::password($_POST['password'], $user['salt']);
if ($user['hash'] == $hash) echo 'Password is correct!';
```

### Email

An email to properly form and send emails with MIME multi-part, database-based queueing and SMTP support.

Two email handling classes are included: EmailSender and EmailStore. See example usage below. Roll your own email sender and email store by extending these abstract classes.

Example usage:
```php
<?php

Firelit\EmailSenderSMTP::config(array(
	'smtp_host' => 'localhost',
	'smtp_port' => 25,
	'smtp_user' => 'example',
	'smtp_pass' => 'elpmaxe'
));

Firelit\EmailStoreDB::config(array(
	'tableName' => 'EmailQueue'
));

$email = new Firelit\Email();

$email->addTo('Jim Bo', 'jimbo@firelit.com'); // Add name & email addresses
$email->to .= ', noname@firelit.com'; // Or set the to, cc & bcc field explicity through their properties

$email->addCc('Accounting', 'accounting@firelit.com');
$email->addBcc('Compliance', 'compliance@firelit.com');

$email->subject = 'An important email';

$email->html = '<h1>Email Test!</h1>'; // Set the html part of the email
$email->text = '*Email Test!*'; // Set the text part of the email

if ($storeAndSend) {
	// You can use the EmailStore object to manage a db-based email queue
	$store = Firelit\EmailStore::init('DB');
	$store->storeAndSend( $email, Firelit\EmailSender::init('SMTP') ); // Store in DB and then try sending
} elseif ($noStoreJustSend) {
	// You can use the EmailSender class to send it out immediately
	$sender = Firelit\EmailSender::init('SMTP');
	$sender->send($email);
}
```

### EndWithError

A class to terminate the script, set the error code and display an error page.

Example usage:
```php
<?php

// Optionally create an HTML page to display
$body = Firelit\EndWithError:createErrorBody('Page Not Found', 'Page Not Found', 'Sorry, we could not find the page you were looking for.');

Firelit\EndWithError::now(404, $body);
```

### HttpRequest

A class to manage new HTTP requests to external web services and websites. Includes file-based cookie support.

Example usage:
```php
<?php

Firelit\HttpRequest::config(array(
	'userAgent' => 'My little user agent string'
));

$http = new Firelit\HttpRequest();

$http->enableCookies();

// 'get', 'post' and 'other' (for put, delete, etc) are also available methods
$http->get('http://www.google.com/');

// Hmmm, I wonder what cookies Google sets...
echo '<pre>'. file_get_contents($http->cookieFile) .'</pre>';
```

### LogEntry 

An application logging class for recording errors, events or other useful data to either a database table or a file. 

Example usage:
```php
<?php

// One-time logger setup
Firelit\LogEntry::config(array(
	'file' => array('enabled' => true)
));

// Standard textual/contextual log entry:
new Firelit\LogEntry(5, 'The website is going down!', __FILE__, __LINE__);

// Exceptions can be logged:
try {
	throw new \Exception('all your base are belong to us');
} catch (Exception $e) {
	new Firelit\LogEntry(4, $e);
}
```

Please remember to restrict access (eg, via .htaccess) to any files you may be using for logging.

### Query

A database interaction class and SQL query creator. Makes database connection management and SQL authoring slightly easier. 

Example usage:
```php
<?php

// One-time connection setup
Firelit\Query::config(array(
	'type' => 'mysql',
	'db_name' => 'database',
	'db_host' => 'localhost', // Hostname or IP acceptable here
	'db_port' => '3306', // Can be left undefined to use default port
	'db_user' => 'username',
	'db_pass' => 'password'
));

// Or specify the DSN string for PDO to connect to other types of databases
Firelit\Query::config(array(
	'type' => 'other',
	'dsn' => 'sqlite::memory:'
));

$q = new Firelit\Query();

$q->insert('TableName', array(
	/* columnName => value */
	'name' => $name,
	'state' => $state
));

if (!$q->success()) die('It did not work :(');

$q->query("SELECT * FROM `TableName` WHERE `name`=:name", array('name' => $name));

while ($row = $q->getRow()) 
	echo $row['name'] .': '. $row['state'] .'<br>';
```

Use the config method to setup the database connection:
- `config( $configArray );`

Available methods for building and executing queries:
- `query( $sql, [ $dataArray ]);`
- `insert( $tableName, $dataArray );`
- `replace( $tableName, $dataArray );`
- `select( $tableName, [ $selectFieldsArray, [ $whereStatement, [ $whereDataArray, [ $limit, [ $range ]]]]] );`
- `update( $tableName, $dataArray, $whereStatement, [ $whereDataArray, [ $limit, [ $range ]]] );`
- `delete( $tableName, $whereStatement, [ $whereDataArray, [ $limit, [ $range ]]] );`

Available methods for getting the status and/or results of a query:
- `getRes();` returns true if the query was successfully executed
- `getRow();` returns the next data row from a successful select query
- `getAll();` returns all the data rows from a successful select query
- `getNewId();` returns the new ID from newly-inserted data row
- `getAffected();` returns the number of rows affected by the query
- `getNumRows();` returns the number of data rows returned by a select query (not reliable for all databases)
- `getError();` returns the error message
- `getErrorCode();` returns the error code
- `success();` returns true if the query was successfully executed
- `logError(LogEntry $logger, $file, $line);` is a helper method for logging any query errors

### ServerRequest

A class that captures the incoming HTTP request in a single object and performs any necessary preliminary work. Provides a nice class wrapper around all the important parameters within the request and allows for easy sanitization.

Example usage:
```php
<?php

$req = new Firelit\ServerRequest( function(&$val) {
	
	// Remove any invalid UTF-8 characters from $_POST, $_GET and $_COOKIE
	Firelit\Strings::cleanUTF8($val); 
	
});

// Filtered $_POST, $_GET and $_COOKIE parameters can then be accessed via the object
if ($req->get['page'] == '2') showPageTwo();
```

Available properties:
- `cli` will return true if the page was loaded from the command line interface
- `cookie` will return all data (filtered, as specified) originally available via $_COOKIE
- `get` will return all data (filtered, as specified) originally available via $_GET
- `header` will return an array of all HTTP headers by key (if Apache is the web server used)
- `host` is set to the host as secified in the HTTP request
- `method` is set to the HTTP request method (eg, 'POST', 'PUT', etc.)
- `path` is set to the requested path (eg, '/folder/test.php')
- `post` will return all data (filtered, as specified) originally available via $_POST
- `referer` will return the HTTP referer as specified by the client
- `secure` will return true if the connection is secure (ie, 'HTTPS://')

### ServerResponse

A class that manages the server's response to an incoming requests. Defaults to buffering output. Includes helper functions which make changing the HTTP response code and performing a redirect much easier. Note that the ApiResponse class inherits from this class to make use of its response management.

### Session

Session management class which can use PHP's native session features (and an optional database store). You can get and set any property name to the session object and it is dynamically saved (using magic getter and setter methods). Implement the PHP-native SessionHandlerInterface to create your own session handler or session storage engine. This library provides database implementation called Firelit\DatabaseSessionHandler. Roll your own by implementing SessionHandlerInterface and use a class of this object when instantiating the Session object. Or, leave this parameter off to simply use PHP's built-in cookie- & file-based session handling.

Note that if you are using Firelit\DatabaseSessionHandler, the expiration of a session is NOT controlled by the `session.gc_maxlifetime` as it is if you use the Session class without the session handler.

Example usage:
```php
<?php

$sess = new Firelit\Session::init(new Firelit\DatabaseSessionHandler);

$sess->loggedIn = true;
$sess->userName = 'Peter';

echo '<p>Hello '. $sess->userName .'</p>';
```

### Strings

A set of string helper functions wrapped into a class.

Example usage:
```php
<?php

Firelit\Strings::cleanUTF8($_POST);
```

### Vars

A class for managing application-level, persistent variables. Vars is implemented through magic setters and getters so you can use any name you want. Storage is maintained by the VarsStore abstract class and data can be held in a file or in a database. For VarsStoreDB, each set or get is equal to one database SQL statement so this can get costly very quick if you are doing a lot of read/writes. Roll your own VarsStore by extending the class.

Example usage:
```php
<?php

$vars = new Firelit\Vars( Firelit\VarsStore::init('DB') );

// Set a persistent application variable
$vars->maintenanceMode = true;

// Read a persistent application variable
if ($vars->maintenanceMode) die('Sorry, under construction.');
```

Auto-Loader Example
-------------------

The beauty of the auto-loader is that it will only load & parse PHP files that it needs. To use it, however, you must define an autoloader function. [Composer](http://getcomposer.org/) normally handles this, but if you are using this library without composer here is an exmaple autoloader that could be used, created by the [PSR-0](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md) Framework Interop Group:

```php
<?php

function autoload($className) {
	$className = ltrim($className, '\\');
	$fileName  = '';
	$namespace = '';
	if ($lastNsPos = strrpos($className, '\\')) {
	  $namespace = substr($className, 0, $lastNsPos);
	  $className = substr($className, $lastNsPos + 1);
	  $fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
	}
	$fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
	
	require $fileName;
}
```
