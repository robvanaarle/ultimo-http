<?php

namespace ultimo\net\http\adapters;

use ultimo\net\http\Request;
use ultimo\net\http\Response;
use ultimo\net\http\Client;

class Curl implements \ultimo\net\http\Adapter {
  /**
   * Executes the http request and returns the response.
   * @param Request $request Http request.
   * @param array $options Request options.
   * @param handle filehandle Handle to a file to store the body of the
   * response to.
   * @return Response $response Http response. 
   */
  public function request(Request $request, array $options, $fileHandle=null) {
    // build query with GET params
    $url = $request->getUrl();
    
    // build headers
    $headers = array();
    foreach ($request->getHeaders() as $header) {
      $headers[] = (string) $header;
    }
    
    // initialize empty array to save headers to
    $responseHeaders = array();
    
    // default options
    $curlOptions = array(
      CURLOPT_SSL_VERIFYPEER => false,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_CUSTOMREQUEST => $request->getMethod(),
      CURLOPT_HTTPHEADER => $headers,
      CURLOPT_HEADER => false,
      CURLOPT_FOLLOWLOCATION => false,
      CURLOPT_HEADERFUNCTION => function($handle, $header) use (&$responseHeaders) {
        $responseHeadersCount = count($responseHeaders);
        // if previous header was empty, and there is a new header, clear
        // all received headers (could be a HTTP/1.1 100 Continue)
        if ($responseHeadersCount > 0 &&
              trim($responseHeaders[$responseHeadersCount-1]) == '') {
          $responseHeaders = array();
        }
        
        $responseHeaders[] = $header;
        return strlen($header);
      }
    );
    
    if (isset($options['proxy'])) {
      $curlOptions[CURLOPT_PROXY] = $options['proxy'];
    }

    if (isset($options['certificates'])) {
      $curlOptions[CURLOPT_CAINFO] = $options['certificates'];
    }
    
    // write to file, if requested
    if ($fileHandle !== null) {
      $curlOptions[CURLOPT_FILE] = $fileHandle;
    }
    
    
    // add POST data, if present
    if ($request->getMethod() == Request::METHOD_POST) {
      if (($request instanceof \ultimo\net\http\XRequest) &&
             $request->getHeader('Content-Type') !== null && 
             $request->getHeader('Content-Type')->getHeaderValue() == \ultimo\net\http\encoders\MultipartFormData::NAME) {
        $curlOptions[CURLOPT_POSTFIELDS] = $this->replaceFiles($request->getPostParams());
      } else {
        $curlOptions[CURLOPT_POSTFIELDS] = $request->getBody();
      }
    }
    
    // initialize curl and execute request
    $handler = curl_init($url);
    curl_setopt_array($handler, $curlOptions);
    $rawResponse = curl_exec($handler);
    $errorMessage = curl_error($handler);
    $errorId = curl_errno($handler);
    curl_close($handler);
    
    // check for errors
    if ($errorId != 0) {
        $code = \ultimo\net\http\HttpException::OTHER;
        $mapping = array(
            6 => \ultimo\net\http\HttpException::UNKNOWN_HOST,
            7 => \ultimo\net\http\HttpException::UNABLE_TO_CONNECT
        );
        if (isset($mapping[$errorId])) {
            $code = $mapping[$errorId];
        }
        
        throw new \ultimo\net\http\HttpException($errorMessage, $code);
    }
    
    
    // construct raw response, depening on whether it was stored to a file
    if (isset($curlOptions[CURLOPT_FILE])) {
      $rawResponse = implode('', $responseHeaders);
    } else {
      $rawResponse = implode('', $responseHeaders) . $rawResponse;
    }
    
    // parse the response
    $response = Response::parse($rawResponse);
    
    // follow location header, if present and max redirects was not reached
    if ($options['followLocationHeader'] &&
        $options['maxRedirects'] > 0 &&
        $response->getHeader('Location') !== null) {

      $options['maxRedirects']--;
      $request = new Request($response->getHeader('Location')->getHeaderValue(), Request::METHOD_GET);
      
      $next = $this->request($request, $options, $fileHandle);
      //$next->previous = $response;
      return $next;
    }

    return $response;
  }
  
  protected function replaceFiles(array $postParams) {
    foreach ($postParams as $key => $value) {
      if (is_array($value)) {
        $postParams[$key] = $this->replaceFiles($postParams);
      } elseif ($value instanceof \ultimo\net\http\File) {
        $postParams[$key] = '@' . $value->path;
        
        foreach ($value->options as $name => $value) {
          $postParams[$key] .= ';' . $name . '=' . $value;
        }
      }
    }
    
    return $postParams;
  }
}
