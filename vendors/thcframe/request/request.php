<?php

namespace THCFrame\Request;

use THCFrame\Core\Base;
use THCFrame\Events\Events as Event;
use THCFrame\Core\StringMethods;
use THCFrame\Request\RequestMethods;
use THCFrame\Request\Exception;

/**
 * Class represents the different types of request methods, but ultimately they all call the
 * same request() method. Other things to note are that the constructor sets the user agent, and the 
 * get method turns a provided parameter array into a valid querystring.
 */
class Request extends Base
{

    protected $_request;

    /**
     * @readwrite
     */
    public $_willFollow = true;

    /**
     * @readwrite
     */
    protected $_willShareSession = true;

    /**
     * @readwrite
     */
    protected $_headers = array();

    /**
     * @readwrite
     */
    protected $_options = array();

    /**
     * @readwrite
     */
    protected $_referer;

    /**
     * @readwrite
     */
    protected $_agent;

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
     * 
     * @param type $key
     * @param type $value
     * @return \THCFrame\Request\Request
     */
    protected function _setOption($key, $value)
    {
        curl_setopt($this->_request, $key, $value);
        return $this;
    }

    /**
     * 
     * @param type $key
     * @return type
     */
    protected function _normalize($key)
    {
        return 'CURLOPT_' . str_replace('CURLOPT_', '', strtoupper($key));
    }

    /**
     * Method sets Curl parameters relating to each of the different request methods.
     * Some request methods need additional parameters set (such as GET and POST), 
     * while others need things excluded from the response (such as HEAD)
     * 
     * @param type $method
     * @return \THCFrame\Request\Request
     */
    protected function _setRequestMethod($method)
    {
        switch (strtoupper($method)) {
            case 'HEAD':
                $this->_setOption(CURLOPT_NOBODY, true);
                break;
            case 'GET':
                $this->_setOption(CURLOPT_HTTPGET, true);
                break;
            case 'POST':
                $this->_setOption(CURLOPT_POST, true);
                break;
            default:
                $this->_setOption(CURLOPT_CUSTOMREQUEST, $method);
                break;
        }

        return $this;
    }

    /**
     * Method iterates through all the request-specific parameters that need to be set.
     * This includes the URL, the user agent, whether the request should follow redirects, and so on. 
     * It even adds any options specified by the use of the setOptions() setter method (or construction option)
     * 
     * @param type $url
     * @param type $parameters
     * @return \THCFrame\Request\Request
     */
    protected function _setRequestOptions($url, $parameters)
    {
        $this->_setOption(CURLOPT_URL, $url)
                ->_setOption(CURLOPT_HEADER, true)
                ->_setOption(CURLOPT_RETURNTRANSFER, true)
                ->_setOption(CURLOPT_USERAGENT, $this->agent);

        if (!empty($parameters)) {
            $this->_setOption(CURLOPT_POSTFIELDS, $parameters);
        }

        if ($this->willFollow) {
            $this->_setOption(CURLOPT_FOLLOWLOCATION, true);
        }

        if ($this->willShareSession) {
            $this->_setOption(CURLOPT_COOKIE, session_name() . '=' . session_id());
        }

        if ($this->referer) {
            $this->_setOption(CURLOPT_REFERER, $this->referer);
        }

        foreach ($this->_options as $key => $value) {
            $this->_setOption(constant($this->_normalize($key)), $value);
        }

        return $this;
    }

    /**
     * Method iterates through the headers specified by the setHeaders()
     * setter method (or construction options) to add any custom headers to the request
     * 
     * @return \THCFrame\Request\Request
     */
    protected function _setRequestHeaders()
    {
        $headers = array();

        foreach ($this->headers as $key => $value) {
            $headers[] = $key . ': ' . $value;
        }

        $this->_setOption(CURLOPT_HTTPHEADER, $headers);
        return $this;
    }

    /**
     * 
     * @param type $options
     */
    public function __construct($options = array())
    {
        parent::__construct($options);
        $this->agent = RequestMethods::server('HTTP_USER_AGENT', 'Curl/PHP ' . PHP_VERSION);
    }

    /**
     * 
     * @param type $url
     * @param type $parameters
     * @return type
     */
    public function delete($url, $parameters = array())
    {
        return $this->request('DELETE', $url, $parameters);
    }

    /**
     * 
     * @param type $url
     * @param type $parameters
     * @return type
     */
    public function get($url, $parameters = array())
    {
        if (!empty($parameters)) {
            $url .= StringMethods::indexOf($url, '?') ? '&' : '?';
            $url .= is_string($parameters) ? $parameters : http_build_query($parameters, '', '&');
        }
        return $this->request('GET', $url);
    }

    /**
     * 
     * @param type $url
     * @param type $parameters
     * @return type
     */
    public function head($url, $parameters = array())
    {
        return $this->request('HEAD', $url, $parameters);
    }

    /**
     * 
     * @param type $url
     * @param type $parameters
     * @return type
     */
    public function post($url, $parameters = array())
    {
        return $this->request('POST', $url, $parameters);
    }

    /**
     * 
     * @param type $url
     * @param type $parameters
     * @return type
     */
    public function put($url, $parameters = array())
    {
        return $this->request('PUT', $url, $parameters);
    }

    /**
     * The request() method use Curl to make the HTTP requests.
     * The method begins by creating a new curl resource instance and continues by setting some parameters of the
     * instance. It then makes the request, and if the request is successful, it will be returned in the form of a
     * Request\Response class instance. If the request fails, an exception will be raised.
     * Finally, the curl resource is destroyed and the response is returned.
     * 
     * @param type $method
     * @param type $url
     * @param type $parameters
     * @return \THCFrame\Request\Request\Response
     * @throws Exception\Response
     */
    public function request($method, $url, $parameters = array())
    {
        session_write_close();

        Event::fire('framework.request.request.before', array($method, $url, $parameters));

        $request = $this->_request = curl_init();

        if (is_array($parameters)) {
            $parameters = http_build_query($parameters, '', '&');
        }

        $this->_setRequestMethod($method)
                ->_setRequestOptions($url, $parameters)
                ->_setRequestHeaders();

        $response = curl_exec($request);

        if (!headers_sent()) {
            session_start();
        }

        if ($response) {
            $response = new Response(array(
                'response' => $response
            ));
        } else {
            throw new Exception\Response(ucfirst(curl_error($request)));
        }

        Event::fire('framework.request.request.after', array($method, $url, $parameters, $response));

        curl_close($request);
        return $response;
    }

}
