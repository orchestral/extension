<?php namespace Orchestra\Extension;

use Illuminate\Http\Request;

class RouteGenerator
{
    /**
     * Request instance.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * Domain name.
     *
     * @var string
     */
    protected $domain = null;

    /**
     * Handles path.
     *
     * @var string
     */
    protected $prefix = null;

    /**
     * Base URL.
     *
     * @var string
     */
    protected $baseUrl = null;

    /**
     * Base URL prefix.
     *
     * @var string
     */
    protected $basePrefix = null;

    /**
     * Construct a new instance.
     *
     * @param  string                      $handles
     * @param  \Illuminate\Http\Request    $request
     */
    public function __construct($handles, Request $request)
    {
        $this->request = $request;

        // Build base URL and prefix from Request::root();
        $baseUrl = str_replace(array('https://', 'http://'), '', $this->request->root());
        $base    = explode('/', $baseUrl, 2);

        if (count($base) > 1) {
            $this->basePrefix = array_pop($base);
        }

        $this->baseUrl = array_shift($base);

        // If the handles doesn't start as "//some.domain.com/foo" we should
        // assume that it doesn't belong to any subdomain, otherwise we
        // need to split the value to "some.domain.com" and "foo".
        if (is_null($handles) or ! starts_with($handles, '//')) {
            $this->prefix = $handles;
        } else {
            $handles      = substr($handles, 2);
            $fragments    = explode('/', $handles, 2);
            $this->domain = array_shift($fragments);
            $this->prefix = array_shift($fragments);
        }

        // It is possible that prefix would be null, in this case assume
        // it handle the main path under the domain.
        ! is_null($this->prefix) or $this->prefix = '/';
    }

    /**
     * Get route domain.
     *
     * @param  boolean  $forceBase
     * @return string
     */
    public function domain($forceBase = false)
    {
        $pattern = $this->domain;

        if (is_null($pattern) and $forceBase === true) {
            $pattern = $this->baseUrl;
        } elseif (str_contains($pattern, '{{domain}}')) {
            $pattern = str_replace('{{domain}}', $this->baseUrl, $pattern);
        }

        return $pattern;
    }

    /**
     * Get route prefix.
     *
     * @param  boolean  $forceBase
     * @return string
     */
    public function prefix($forceBase = false)
    {
        $pattern = trim($this->prefix, '/');

        if (is_null($this->domain) and $forceBase === true) {
            $pattern = trim($this->basePrefix, '/')."/{$pattern}";
            $pattern = trim($pattern, '/');
        }

        empty($pattern) and $pattern = '/';

        return $pattern;
    }

    /**
     * Get route root.
     *
     * @return string
     */
    public function root()
    {
        $http   = ($this->request->secure() ? "https" : "http");
        $domain = trim($this->domain(true), '/');
        $prefix = $this->prefix(true);

        return trim("{$http}://{$domain}/{$prefix}", '/');
    }

    /**
     * Get route to.
     *
     * @param  string   $to
     * @return string
     */
    public function to($to)
    {
        $root    = $this->root();
        $to      = trim($to, '/');
        $pattern = "{$root}/{$to}";

        return $pattern !== '/' ? $pattern : '';
    }

    /**
     * Magic method to parse as string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->prefix();
    }
}
