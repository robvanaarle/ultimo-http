# Ultimo Http
HTTP library

## Features
* HTTP request and response
* HTTP headers
* HTTP encodings
* Files
* Get current request depending on PHP SAPI

## Requirements
* PHP 5.3
* curl (optional)

## Usage

    $request = new \ultimo\net\http\Request('http://www.server.com');
	$request->addHeader(\ultimo\net\http\headers\Cookie(array('sessionid' => 'abc')));

	$http = new \ultimo\net\http\Client(new \ultimo\net\http\adapters\Curl());
	$response = $http->request($request);

	$contentType = $response->getHeader('Content-Type');
	$body = $response->getBody();
	