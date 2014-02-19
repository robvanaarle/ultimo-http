<?php

namespace ultimo\net\http;

interface Adapter {
  /**
   * Executes the http request and returns the response.
   * @param Request $request Http request.
   * @param array $options Request options.
   * @param handle filehandle Handle to a file to store the body of the
   * response to.
   * @return Response $response Http response. 
   */
  public function request(Request $request, array $options, $fileHandle=null);
}