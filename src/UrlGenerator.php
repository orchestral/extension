<?php

namespace Orchestra\Extension;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Orchestra\Contracts\Extension\UrlGenerator as UrlGeneratorContract;

class UrlGenerator implements UrlGeneratorContract
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
     * @var string|null
     */
    protected $domain;

    /**
     * Handles path.
     *
     * @var string|null
     */
    protected $prefix;

    /**
     * Base URL.
     *
     * @var string|null
     */
    protected $baseUrl;

    /**
     * Base URL prefix.
     *
     * @var string|null
     */
    protected $basePrefix;

    /**
     * The URL schema to be forced on all generated URLs.
     *
     * @var string|null
     */
    protected $forceSchema;

    /**
     * Construct a new instance.
     *
     * @param \Illuminate\Http\Request  $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Get the scheme for a raw URL.
     *
     * @param  bool|null  $secure
     *
     * @return string
     */
    protected function getScheme(?bool $secure): string
    {
        if (\is_null($secure)) {
            return $this->forceSchema ?: $this->request->getScheme().'://';
        }

        return $secure ? 'https://' : 'http://';
    }

    /**
     * Force the schema for URLs.
     *
     * @param  string  $schema
     *
     * @return void
     */
    public function forceScheme(string $schema): void
    {
        $this->forceSchema = $schema.'://';
    }

    /**
     * Build route.
     *
     * @param  string  $handles
     *
     * @return $this
     */
    public function handle(string $handles)
    {
        // If the handles doesn't start as "//some.domain.com/foo" we should
        // assume that it doesn't belong to any subdomain, otherwise we
        // need to split the value to "some.domain.com" and "foo".
        if (\is_null($handles) || ! Str::startsWith($handles, ['//', 'http://', 'https://'])) {
            $this->prefix = $handles;
        } else {
            $handles = \substr(\str_replace(['http://', 'https://'], '//', $handles), 2);
            $fragments = \explode('/', $handles, 2);
            $this->domain = \array_shift($fragments);
            $this->prefix = \array_shift($fragments);
        }

        // It is possible that prefix would be null, in this case assume
        // it handle the main path under the domain.
        ! \is_null($this->prefix) || $this->prefix = '/';

        return $this;
    }

    /**
     * Get route domain.
     *
     * @param  bool  $forceBase
     *
     * @return string|null
     */
    public function domain(bool $forceBase = false): ?string
    {
        $pattern = $this->domain;
        $baseUrl = $this->getBaseUrl();

        if (\is_null($pattern) && $forceBase === true) {
            $pattern = $baseUrl;
        } elseif (Str::contains($pattern, '{{domain}}')) {
            $pattern = \str_replace('{{domain}}', $baseUrl, $pattern);
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
    public function group(bool $forceBase = false): array
    {
        $group = [
            'prefix' => $this->prefix($forceBase),
        ];

        if (! \is_null($domain = $this->domain($forceBase))) {
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
    public function is(string $pattern): bool
    {
        $path = $this->path();
        $prefix = $this->prefix();

        foreach (\func_get_args() as $pattern) {
            $pattern = ($pattern === '*' ? "{$prefix}*" : "{$prefix}/{$pattern}");
            $pattern = \trim($pattern, '/');

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
    public function path(): string
    {
        $pattern = \trim($this->request->path(), '/');

        return $pattern === '' ? '/' : $pattern;
    }

    /**
     * Get route prefix.
     *
     * @param  bool  $forceBase
     *
     * @return string
     */
    public function prefix(bool $forceBase = false): string
    {
        $pattern = \trim($this->prefix, '/');

        if (\is_null($this->domain) && $forceBase === true) {
            $pattern = \trim($this->basePrefix, '/')."/{$pattern}";
            $pattern = \trim($pattern, '/');
        }

        empty($pattern) && $pattern = '/';

        return $pattern;
    }

    /**
     * Get route root.
     *
     * @return string
     */
    public function root(): string
    {
        $scheme = $this->getScheme(null);
        $domain = \trim($this->domain(true), '/');
        $prefix = $this->prefix(true);

        return \trim("{$scheme}{$domain}/{$prefix}", '/');
    }

    /**
     * Get base url.
     *
     * @return string
     */
    public function getBaseUrl(): string
    {
        if (\is_null($this->baseUrl)) {
            $this->resolveBaseUrlFrom($this->request->root());
        }

        return $this->baseUrl;
    }

    /**
     * Set base URL.
     *
     * @param  string  $root
     *
     * @return $this
     */
    public function setBaseUrl(string $root)
    {
        if (! empty($root)) {
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
    public function to(string $to): string
    {
        $root = $this->root();
        $to = \trim($to, '/');
        $pattern = \trim("{$root}/{$to}", '/');

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
     * @param  string|null  $root
     *
     * @return void
     */
    protected function resolveBaseUrlFrom(?string $root): void
    {
        // Build base URL and prefix.
        $baseUrl = \ltrim(\str_replace(['https://', 'http://'], '', $root), '/');
        $base = \explode('/', $baseUrl, 2);

        if (\count($base) > 1) {
            $this->basePrefix = \array_pop($base);
        }

        $this->baseUrl = \array_shift($base);
    }
}
