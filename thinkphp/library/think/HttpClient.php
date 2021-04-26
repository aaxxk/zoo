<?php

namespace think;

class HttpClient {

    /**
     * Slow request time, 3 seconds by default
     */
    const SLOW_REQUEST_TIME = 3;

    /**
     * Requested url address
     * @var string
     */
    private $_request_url;

    /**
     * Requested port
     * @var int
     */
    private $_request_port;

    /**
     * Requested cookie
     * @var string
     */
    private $_request_cookie;

    /**
     * Request content, please provide the requested content as key=value&key2=value2
     * @var string
     */
    private $_request_data;

    /**
     * Files to upload
     * @var array
     */
    private $_request_files = array();

    /**
     * Request method, the default is POST method
     * @var string
     */
    private $_method = 'GET';

    /**
     * Certificate file
     */
    private $_cert_file;

    /**
     * Certificate password
     * @var string
     */
    private $_cert_passwd;

    /**
     * Certificate type PEM
     * @var string
     */
    private $_cert_type = 'PEM';

    /**
     * CA file
     * @var string
     */
    private $_ca_file;

    /**
     * error code
     * @var
     */
    private $_errno;

    /**
     * Error message
     * @var string
     */
    private $_error;

    /**
     * overtime time
     * @var int Unit milliseconds
     */
    private $_timeout = 3000;

    /**
     * HTTP HEADER
     * @var array
     */
    private $_headers = array();

    /**
     * UserAgent information
     * @var string
     */
    private $_user_agent;

    /**
     * http status code
     * @var int
     */
    private $_response_code = 0;

    /**
     * Response header
     * @var string
     */
    private $_response_header;

    /**
     * Response content
     * @var string
     */
    private $_response_body;

    /**
     * http Log name
     * @var string
     */
    private $_log_name = 'http_slow_request';

    /**
     * curl Handle resource information
     * @var array
     */
    private $_curl_info = array();

    /**
     * Constructor
     */

    /**
     * HttpClient constructor.
     * @param string $url Request address
     * @param string $method Request method get|post
     * @param int $timeout Timeout time, in milliseconds, default 30 seconds
     */
    public function __construct($url = '', $method = 'GET', $timeout = 30000) {
        $this->setRequestUrl($url);
        $this->setMethod($method);
        $this->setTimeout($timeout);
    }

    /**
     * Set request address
     * @param string $url
     * @return bool
     */
    public function setRequestUrl($url) {
        $this->_request_url = $url;
        return true;
    }

    /**
     * Set request port
     * @param int $port
     * @return bool
     */
    public function setRequestPort($port) {
        $this->_request_port = (int) $port;
        return true;
    }

    /**
     * Set request cookie
     * @param array|string $data
     * @return bool
     */
    public function setCookie($data) {
        if (is_array($data)) {
            $this->_request_cookie = http_build_query($data, '', ';');
        } else {
            $this->_request_cookie = $data;
        }

        return true;
    }

    /**
     * Set the requested content
     * @param string|array $data
     * @return bool
     */
    public function setRequestBody($data) {
        if (is_array($data)) {
            $this->_request_data = http_build_query($data);
        } else {
            $this->_request_data = $data;
        }

        return true;
    }

    public function setRequestFile($file, $postname = null, $filename = null) {
        if (!is_readable($file)) {
            return false;
        }

        if ($filename === null) {
            $filename = basename($file);
        }
        if ($postname === null) {
            $postname = uniqid();
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimetype = finfo_file($finfo, $file);

        if (function_exists('curl_file_create')) {
            $cfile = curl_file_create($file, $mimetype, $filename);
        } else {
            $cfile = "@{$file};filename={$filename}"
                    . ($mimetype ? ";type={$mimetype}" : '');
        }
        $this->_request_files[$postname] = $cfile;

        return true;
    }

    /**
     * Set request method
     * @param string $method
     */
    public function setMethod($method) {
        if (!in_array(strtoupper($method), array('POST', 'GET'))) {
            $method = 'GET';
        }

        $this->_method = strtoupper($method);
    }

    /**
     * Set certificate information
     * @param string $cert_file
     * @param string $cert_passwd
     * @param string $cert_type
     * @return bool
     */
    public function setCertInfo($cert_file, $cert_passwd = '', $cert_type = "PEM") {
        if (!is_readable($cert_file) || empty($cert_type)) {
            return false;
        }
        $this->_cert_file = $cert_file;
        $this->_cert_passwd = $cert_passwd;
        $this->_cert_type = $cert_type;
        return true;
    }

    /**
     * Set up the CA
     * @param string $ca_file
     * @return bool
     */
    public function setCaInfo($ca_file) {
        if (!is_readable($ca_file)) {
            return false;
        }
        $this->_ca_file = $ca_file;
        return true;
    }

    /**
     * Set the timeout time, in milliseconds
     * @param $timeout
     * @return bool
     */
    public function setTimeout($timeout) {
        $this->_timeout = (int) $timeout;
        return true;
    }

    /**
     * Set HttpHeader
     * @param array $headers
     * @return bool
     */
    public function setRequestHeader(array $headers) {
        if (empty($headers)) {
            return false;
        }

        $this->_headers = $headers;
        return true;
    }

    /**
     * Set up UserAgent
     * @param string $user_agent
     * @return bool
     */
    public function setUserAgent($user_agent) {
        if (!empty($user_agent)) {
            $this->_user_agent = $user_agent;
        }

        return true;
    }

    /**
     * Get http response status code
     * @return int
     */
    public function getResponseCode() {
        return $this->_response_code;
    }

    /**
     * Get UserAgent
     * @return string
     */
    public function getUserAgent() {
        return $this->_user_agent;
    }

    /**
     * Get curl handle information
     * @return array
     */
    public function getCurlInfo() {
        return $this->_curl_info;
    }

    /**
     * Get response header information
     * @return string
     */
    public function getResponseHeader() {
        return $this->_response_header;
    }

    /**
     * Get response result information
     * @return string
     */
    public function getResponseBody() {
        return $this->_response_body;
    }

    /**
     * Get response cookie
     * @return array
     */
    public function getResponseCookie() {
        $_cookie = array();
        if (!empty($this->_response_header)) {
            $temp = array();
            preg_match_all('/Set-Cookie:\s*([^=]+)=([^;]+);*/i', $this->_response_header, $temp);
            if (is_array($temp) && isset($temp[1]) && isset($temp[2])) {
                $_cookie = array_combine($temp[1], array_map('urldecode', $temp[2]));
            }
        }
        return $_cookie;
    }

    /**
     * Get error code
     * @return int
     */
    public function getErrNo() {
        return $this->_errno;
    }

    /**
     * Get error code
     * @return string
     */
    public function getError() {
        return $this->_error;
    }

    /**
     * Perform remote request
     * @return bool
     */
    public function exec() {
        if (empty($this->_request_url)) {
            return false;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, $this->_timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        }

        if ($this->_method == 'GET') {
            if ($this->_request_data !== null) {
                $this->_request_url .= ((strpos($this->_request_url, '?') === false) ? '?' : '&') . $this->_request_data;
            }
            curl_setopt($ch, CURLOPT_URL, $this->_request_url);
        } else {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_URL, $this->_request_url);
            if (!empty($this->_request_files)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $this->_request_files);
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $this->_request_data);
            }
        }

        //Set HttpHeader
        if ($this->_headers !== null) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->_headers);
        }
        curl_setopt($ch, CURLOPT_HEADER, true);

        if ($this->_request_port !== null) {
            curl_setopt($ch, CURLOPT_PORT, $this->_request_port);
        }

        if ($this->_request_cookie !== null) {
            curl_setopt($ch, CURLOPT_COOKIE, $this->_request_cookie);
        }

        if ($this->_user_agent) {
            curl_setopt($ch, CURLOPT_USERAGENT, $this->_user_agent);
        }

        //Set certificate information
        if ($this->_cert_file !== null) {
            curl_setopt($ch, CURLOPT_SSLCERT, $this->_cert_file);
            curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $this->_cert_passwd);
            curl_setopt($ch, CURLOPT_SSLCERTTYPE, $this->_cert_type);
        }

        //Set up the CA
        if ($this->_ca_file !== null) {
            // Check the source of the certificate, 0 means to prevent the check of the legality of the certificate. 1 Need to set CURLOPT_CAINFO
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_CAINFO, $this->_ca_file);
        } else {
            // Check the source of the certificate, 0 means to prevent the check of the legality of the certificate. 1 Need to set CURLOPT_CAINFO
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }

        $response = curl_exec($ch);

        $this->_curl_info = curl_getinfo($ch);

        $this->_response_code = isset($this->_curl_info['http_code']) ? $this->_curl_info['http_code'] : 0;

        $this->_errno = curl_errno($ch);
        $this->_error = curl_error($ch);
        if ($this->_errno > 0) {
            curl_close($ch);
            return false;
        }

        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $this->_response_header = substr($response, 0, $headerSize);
        $this->_response_body = substr($response, $headerSize);
        return true;
    }
}
