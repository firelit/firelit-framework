Firelit-Framework
===============
[![Build Status](https://travis-ci.org/firelit/firelit-framework.png?branch=master)](https://travis-ci.org/firelit/firelit-framework)

Firelit's standard PHP framework provides a set of helpful classes for developing a website. They are created and namespaced so that they can easily be used with an auto-loader, following the [PSR-4 standard](http://www.php-fig.org/psr/psr-4/).

Requirements
------------

- PHP version 5.4.0 or higher

External PHP Extensions:
- OpenSSL extension (required for `Crypto` and `CryptoKey` class)
- cURL extension (required for `HttpRequest` class)
- Database-specific PDO extension (e.g., `pdo-mysql`, required for `Query` class)

How to Use
----------

The easiest way to use this library is to use [Composer](http://getcomposer.org/) which automatically handles dependencies and auto-loading.

Here is an example of how you'd add this package to your `composer.json` under the require key:
```js
    "require": {
        "firelit/framework": "^2.0"
    }
```

You could also add it from the command line as follows:
```
php composer.phar require firelit/framework "^2.0"
```

Alternatively, you could go the manual way and setup your own autoloader and copy the project files from `lib/` into your project directory.

MVC Architecture
----------------

This framework comes with classes to support building apps with the MVC architecture.
- `Firelit\View` class
- `Firelit\Controller` class
- `Firelit\DatabaseObject` (i.e., model) class
- `Firelit\Router` class

An example implementation using these classes in a single entry web app:

```php
<?php

// Setup
$resp = Firelit\Response::init();
$reqs = Firelit\Request::init();
$router = Firelit\Router::init($reqs);

$router->exceptionHandler(function($e) use ($resp) {
    $resp->setCode(500);
    echo $e->getMessage();
    exit;
});

$router->add('GET', '!^/Hello$!', function() {
	// Simple route, you'd go to http://example.com/Hello and get this:
    echo 'World!';
});

$router->add('GET', '!^/redirect$!', function() use ($resp) {
	// Redirect example
    $resp->redirect('/to/here');
});

$router->add('POST', '!^/forms!', function() {
    // Process the POST request in a controller
    Firelit\Controller::handoff('Controller\Forms', 'process');
});

$router->add('GET', '!^/groups/([^0-9]+)$!', function($matches) {
	// Match URL parts with regular expressions to extract information
	echo 'You selected group #'. $matches[0];
});

$router->defaultRoute(function() use ($resp) {
	// A default route is a backstop, catching any routes that don't match
    $resp->code(404);
    echo 'Sorry, no can do';
});
```

Note that this setup is considered single-entry so there must be a slight modification to web server to force it to use the main script (e.g., index.php) for all HTTP requests. Here's an example `.htaccess` (from the WordPress project) that will configure Apache to route all requests to a single entry script.

```apache
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
```

Classes Included
----------------

### ApiResponse

A response-handling class for API end-points. Can handle all HTTP response codes and JSON & _limited_ XML. Set a template to ensure some fields are always sent back with the response.

Example usage:
```php
<?php

$resp = Firelit\ApiResponse::init('JSON');

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
			array(
				'host' => 'localhost',
				'port' => 11211,
				'persistent' => true,
				'weight' => 1,
				'timeout' => 1
			)
			/* Multiple servers can be added */
		)
	)
));

$val = Firelit\Cache::get('randomValue', function() {

	// If cache miss, this closure will execute this closure, storing the returned value in cache
	return mt_rand(0, 1000);

});

if (Firelit\Cache::$cacheHit)
	echo 'Cache hit!';

// Set a value to null in order to remove it from cache
Firelit\Cache::set('randomValue', null);
```

### Crypto Classes

Crypto, CryptoKey and CryptoPackage are encryption/decryption helper classes using OpenSSL (used in lieu of mcrypt based on [this article](https://paragonie.com/blog/2015/05/if-you-re-typing-word-mcrypt-into-your-code-you-re-doing-it-wrong)). These classes can generate cryptographically secure secure keys and encrypt and decrypt using industry-standard symmetric encryption (RSA) and private key encryption (AES) schemes.

Note that AES encryption will not work for large strings (80 characters or more, depending on key bit size) due to the amount of processing power it takes -- it quickly becomes inefficient. For larger strings, the plain text should be encrypted with RSA and the encryption key should be encrypted with AES. This is exactly what CryptoPackage does for you on top of serializing/unserializing the subject to quickly store and retrieve variables of any type (string, object, array, etc) in an encrypted store.

Example encryption/decryption usage:
```php
<?php

$mySecret = 'Super secret!';

// Private key encryption
$key = Firelit\CryptoKey::newPrivateKey(); // Can be 1024, 2048 or 3072-bit
$crypto = new Firelit\Crypto($key);

$ciphertext = $crypto->encrypt($mySecret)->with(Firelit\Crypto::PUBLIC_KEY);

$plaintext = $crypto->decrypt($ciphertext)->with(Firelit\Crypto::PRIVATE_KEY);

// Symmetric key encryption
$key = Firelit\CryptoKey::newSymmetricKey(); // Can be 128, 192 or 256-bit
$crypto = new Firelit\Crypto($key);

$ciphertext = $crypto->encrypt($mySecret);

$plaintext = $crypto->decrypt($ciphertext);

// Robust, mixed-type private key encryption
$key = Firelit\CryptoKey::newPrivateKey(); // Can be 1024, 2048 or 3072-bit
$crypto = new Firelit\CryptoPackage($key);

$object = (object) array(
	'test' => true,
	'string' => $mySecret . $mySecret . $mySecret
);

$ciphertext = $crypto->encrypt($object)->with(Firelit\Crypto::PUBLIC_KEY);

$objectBack = $crypto->decrypt($ciphertext)->with(Firelit\Crypto::PRIVATE_KEY);
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

### QueryIterator

A PHP [iterator](http://php.net/manual/en/class.iterator.php) that allows for a Query result set to be passed around without actually pre-retrieving all results. The QueryIterator object can then be used in a `foreach` loop, where it fetches the next row needed on-demand.

Example usage:
```php
<?php

function demo() {

	$q = new Firelit\Query($type);

	$q->query("SELECT * FROM `TableName` WHERE `type`=:type", array('type' => $type));

	return new Firelit\QueryIterator($q);

}

$results = demo('best_ones');

foreach ($results as $index => $value) {
	// Do something!
}

```

The QueryIterator constructor takes two parameters, the second optional: The Query object and the object into which the table row should be [fetched](http://php.net/manual/en/pdostatement.fetchobject.php).

### Request

A class that captures the incoming HTTP request in a single object and performs any necessary preliminary work. Provides a nice class wrapper around all the important parameters within the request and allows for easy sanitization.

Example usage:
```php
<?php

$req = new Firelit\Request::init( function(&$val) {

	// Remove any invalid UTF-8 characters from $_POST, $_GET and $_COOKIE
	Firelit\Strings::cleanUTF8($val);

});

// Filtered $_POST, $_GET and $_COOKIE parameters can then be accessed via the object
if ($req->get['page'] == '2') showPageTwo();
```

Example usage:
```php
<?php
// Handle JSON body parsing and PUT requests automatically
$req = new Firelit\Request::init(false, 'json');

// Filtered $_POST, $_GET and $_COOKIE parameters can then be accessed via the object
$newName = $req->put['name'];
```

Available properties:
- `cli` will return true if the page was loaded from the command line interface
- `cookie` will return all data (filtered, as specified) originally available via $_COOKIE
- `get` will return all data (filtered, as specified) originally available via $_GET
- `headers` will return an array of all HTTP headers by key (if Apache is the web server used)
- `host` is set to the host as secified in the HTTP request
- `method` is set to the HTTP request method (eg, 'POST', 'PUT', etc.)
- `path` is set to the requested path (eg, '/folder/test.php')
- `post` will return all data (filtered, as specified) originally available via $_POST
- `protocol` will return the request protocol (eg, HTTP 1.0 or HTTP 1.1)
- `proxies` will return an array of IPs that may be in use as proxies
- `put` will return all data (filtered, as specified) in HTTP body (if PUT request)
- `referer` will return the HTTP referer as specified by the client
- `secure` will return true if the connection is secure (ie, 'HTTPS://')
- `uri` will return the full URI of the request, including HTTP or HTTPS

### Response

A class that manages the server's response to an incoming requests. Defaults to buffering output. Includes helper functions which make changing the HTTP response code and performing a redirect much easier. Note that the ApiResponse class inherits from this class to make use of its response management.

Available methods:
- `contentType()` can be used to set the content-type of the response (eg, 'application/json')
- `redirect()` can be used to redirect the visitor to another URI
- `setCallback()` can be used to set a callback function to handle the server output before it is sent
- `setCode()` can be used to set the HTTP response code (eg, 404)

### Session

Session management class which can use PHP's native session features (and an optional database store). You can get and set any property name to the session object and it is dynamically saved (using magic getter and setter methods). Implement the PHP-native SessionHandlerInterface to create your own session handler or session storage engine. This library provides database implementation called Firelit\DatabaseSessionHandler. Roll your own by implementing SessionHandlerInterface and use a class of this object when instantiating the Session object. Or, leave this parameter off to simply use PHP's built-in cookie- & file-based session handling.

Note that if you are using Firelit\DatabaseSessionHandler, the expiration of a session is NOT controlled by the `session.gc_maxlifetime` as it is if you use the Session class without the session handler.

Example usage:
```php
<?php

$sess = new Firelit\Session::init(new Firelit\DatabaseSessionHandler);

$sess->loggedIn = true;
$sess->userName = 'Peter';

echo '<p>Hello '. htmlentities($sess->userName) .'</p>';
```

### Strings

A set of string helper functions wrapped into a class.

Example usage:
```php
<?php
// Check if an email is valid
$valid = Firelit\Strings::validEmail('test@test.com'); // $valid == true

// Clean the strings in the array for all valid UTF8
Firelit\Strings::cleanUTF8($_POST); // The parameter is passed by reference and directly filtered

// Normalize the formatting on a name or address
$out = Firelit\Strings::nameFix('JOHN P.  DePrez SR'); // $out == 'John P. DePrez Sr.'
$out = Firelit\Strings::addressFix('po box 3484'); // $out == 'PO Box 3484'

// Multi-byte HTML and XML escaping
$out = Firelit\Strings::html('You & I Rock'); // $out == 'You &amp; I Rock'
$out = Firelit\Strings::xml('You & I Rock'); // $out == 'You &#38; I Rock'

// Format the string as a CSV cell value (escaping quotes with a second quote)
$out = Firelit\Strings::cleanUTF8('John "Hairy" Smith'); // $out == '"John ""Hairy"" Smith"'

// Multi-byte safe string case maniuplation
$out = Firelit\Strings::upper('this started lower'); // $out == 'THIS STARTED LOWER'
$out = Firelit\Strings::lower('THIS STARTED UPPER'); // $out == 'this started upper'
$out = Firelit\Strings::title('this STARTED mixed'); // $out == 'This Started Mixed'
$out = Firelit\Strings::ucwords('this STARTED mixed'); // $out == 'This STARTED Mixed'

```

### Vars

A class for managing application-level, persistent variables. Vars is implemented through magic setters and getters so you can use any name you want for your vars and any type of persistant data store. The store can be custom defined by creating custom getter and setter functions (e.g., for reading/writing the values to a file) or you can leave it to the default (which stores the values in a database).

Example usage:
```php
<?php
// Configuration
new Firelit\Vars::init(array(
	'table' => 'Vars',
	'col_name' => 'Name',
	'col_value' => 'Value'
));

// Later usage
$vars = new Firelit\Vars::init();

// Set a persistent application variable with any name accepted by your store
$vars->maintenanceMode = true;

// Read a persistent application variable
if ($vars->maintenanceMode) die('Sorry, under construction.');
```
