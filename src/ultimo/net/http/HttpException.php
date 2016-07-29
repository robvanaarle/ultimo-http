<?php

namespace ultimo\net\http;

class HttpException extends Exception {
    const UNKNOWN_HOST = 1;
    const UNABLE_TO_CONNECT = 2;
    const OTHER = 999;
}