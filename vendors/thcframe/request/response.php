<?php

namespace THCFrame\Request;

use THCFrame\Core\Base;
use THCFrame\Request\Exception;

/**
 * Class accepts a response constructor option, which is the result of an HTTP request. 
 * It splits this response string into headers and a body, which are available through getter
 * methods
 */
class Response extends Base
{

    protected $_response;

    /**
     * @read
     */
    protected $_body = null;

    /**
     * @read
     */
    protected $_headers = array();

    /**
     * 
     * @param type $method
     * @return \THCFrame\Request\Exception\Implementation
     */
    protected function _getImplementationException($method)
    {
        return new Exception\Implementation(sprintf('%s method not implemented', $method));
    }

    /**
     * Object constructor
     * 
     * @param array $options
     */
    public function __construct($options = array())
    {
        if (!empty($options['response'])) {
            $response = $this->_response = $options['response'];
            unset($options['response']);
        }

        parent::__construct($options);

        $pattern = '#HTTP/\d\.\d.*?$.*?\r\n\r\n#ims';
        preg_match_all($pattern, $response, $matches);

        $headers = array_pop($matches[0]);
        $headers = explode('\r\n', str_replace('\r\n\r\n', '', $headers));

        $this->_body = str_replace($headers, '', $response);

        $version = array_shift($headers);
        preg_match('#HTTP/(\d\.\d)\s(\d\d\d)\s(.*)#', $version, $matches);

        $this->_headers['Http-Version'] = $matches[1];
        $this->_headers['Status-Code'] = $matches[2];
        $this->_headers['Status'] = $matches[2] . ' ' . $matches[3];

        foreach ($headers as $header) {
            preg_match('#(.*?)\:\s(.*)#', $header, $matches);
            $this->_headers[$matches[1]] = $matches[2];
        }
    }

    /**
     * 
     * @return type
     */
    public function __toString()
    {
        return $this->body;
    }

}
