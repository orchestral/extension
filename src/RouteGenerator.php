<?php

namespace Orchestra\Extension;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Orchestra\Contracts\Extension\RouteGenerator as RouteGeneratorContract;

class RouteGenerator implements RouteGeneratorContract
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
     * @param  \Illuminate\Http\Request  $request
     * @param  string|null  $baseUrl
     */
    public function __construct(Request $request, $baseUrl = null)
    {
        $this->request = $request;

        $this->setBaseUrl($baseUrl ?: $this->request->root());
    }

    /**
     * Build route.
     *
     * @param  string  $handles
     *
     * @return $this
     */
    public function make($handles)
    {
        // If the handles doesn't start as "//some.domain.com/foo" we should
        // assume that it doesn't belong to any subdomain, otherwise we
        // need to split the value to "some.domain.com" and "foo".
        if (is_null($handles) || ! Str::startsWith($handles, ['//', 'http://', 'https://'])) {
            $this->prefix = $handles;
        } else {
            $handles      = substr(str_replace(['http://', 'https://'], '//', $handles), 2);
            $fragments    = explode('/', $handles, 2);
            $this->domain = array_shift($fragments);
            $this->prefix = array_shift($fragments);
        }

        // It is possible that prefix would be null, in this case assume
        // it handle the main path under the domain.
        ! is_null($this->prefix) || $this->prefix = '/';

        return $this;
    }

    /**
     * Get route domain.
     *
     * @param  bool  $forceBase
     *
     * @return string|null
     */
    public function domain($forceBase = false)
    {
        $pattern = $this->domain;

        if (is_null($pattern) && $forceBase === true) {
            $pattern = $this->baseUrl;
        } elseif (Str::contains($pattern, '{{domain}}')) {
            $pattern = str_replace('{{domain}}', $this->baseUrl, $pattern);
        }

        return $pattern;
    }

    /**
     * Get route group.
     *
     * @param  bool  $forceBase
     *
     * @return array
     */
    public function group($forceBase = false)
    {
        $group = [
            'prefix' => $this->prefix($forceBase),
        ];

        if (! is_null($domain = $this->domain($forceBase))) {
            $group['domain'] = $domain;
        }

        return $group;
    }

    /**
     * Determine if the current request URI matches a pattern.
     *
     * @param  string  $pattern
     *
     * @return bool
     */
    public function is($pattern)
    {
        $path   = $this->path();
        $prefix = $this->prefix();

        foreach (func_get_args() as $pattern) {
            $pattern = ($pattern === '*' ? "{$prefix}*" : "{$prefix}/{$pattern}");
            $pattern = trim($pattern, '/');

            empty($pattern) && $pattern = '/';

            if (Str::is($pattern, $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the current path info for the request.
     *
     * @return string
     */
    public function path()
    {
        $pattern = trim($this->request->path(), '/');

        return $pattern === '' ? '/' : $pattern;
    }

    /**
     * Get route prefix.
     *
     * @param  bool  $forceBase
     *
     * @return string
     */
    public function prefix($forceBase = false)
    {
        $pattern = trim($this->prefix, '/');

        if (is_null($this->domain) && $forceBase === true) {
            $pattern = trim($this->basePrefix, '/')."/{$pattern}";
            $pattern = trim($pattern, '/');
        }

        empty($pattern) && $pattern = '/';

        return $pattern;
    }

    /**
     * Get route root.
     *
     * @return string
     */
    public function root()
    {
        $http   = ($this->request->secure() ? 'https' : 'http');
        $domain = trim($this->domain(true), '/');
        $prefix = $this->prefix(true);

        return trim("{$http}://{$domain}/{$prefix}", '/');
    }

    /**
     * Set base URL.
     *
     * @param  string  $root
     *
     * @return $this
     */
    public function setBaseUrl($root)
    {
        if (is_null($root)) {
            $this->resolveBaseUrlFrom($root);
        }

        return $this;
    }

    /**
     * Get route to.
     *
     * @param  string  $to
     *
     * @return string
     */
    public function to($to)
    {
        $root    = $this->root();
        $to      = trim($to, '/');
        $pattern = trim("{$root}/{$to}", '/');

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

    /**
     * Resolve base url from given path.
     *
     * @param  string  $root
     *
     * @return string
     */
    protected function resolveBaseUrlFrom($root)
    {
        // Build base URL and prefix.
        $baseUrl = str_replace(['https://', 'http://'], '', $root);
        $base    = explode('/', $baseUrl, 2);

        if (count($base) > 1) {
            $this->basePrefix = array_pop($base);
        }

        return $this->baseUrl = array_shift($base);
    }
}
