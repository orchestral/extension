<?php namespace Orchestra\Extension;

class RouteGenerator {

	/**
	 * Domain name.
	 *
	 * @var string
	 */
	protected $domain = null;

	/**
	 * Secured request.
	 *
	 * @var boolean
	 */
	protected $secure = false;

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
	 * @param  string   $handles
	 */
	public function __construct($handles, $baseUrl = null, $secure = false)
	{
		$baseUrl      = str_replace(array('https://', 'http://'), '', $baseUrl);
		$this->secure = $secure;

		// Build base URL and prefix from Request::root();
		$base = explode('/', $baseUrl, 2);
		if (count($base) > 1) $this->basePrefix = array_pop($base);
		$this->baseUrl = array_shift($base);

		// If the handles doesn't start as "//some.domain.com/foo" we should 
		// assume that it doesn't belong to any subdomain, otherwise we 
		// need to split the value to "some.domain.com" and "foo".
		if (is_null($handles) or ! starts_with($handles, '//')) 
		{
			$this->prefix = $handles;
		}
		else
		{
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
	 * Get route root.
	 *
	 * @return string
	 */
	public function root()
	{
		$http   = ($this->secure ? "https" : "http");
		$domain = "{$http}://".trim($this->domain(true), '/');
		$prefix = $this->prefix(true);

		return trim("{$domain}/{$prefix}", '/');
	}

	/**
	 * Get route to.
	 * 
	 * @param  string   $to
	 * @return string
	 */
	public function to($to)
	{
		$root = $this->root();
		return trim("{$root}/{$to}", '/');
	}

	/**
	 * Get route domain.
	 *
	 * @return string
	 */
	public function domain($forceBase = false)
	{
		$domain = $this->domain;
		
		if (is_null($domain)) 
		{
			if ($forceBase === true)
			{
				$domain = $this->baseUrl;
			}
		}

		return $domain;
	}

	/**
	 * Get route prefix.
	 *
	 * @return string
	 */
	public function prefix($forceBase = false)
	{
		$prefix = trim($this->prefix, '/');

		if (is_null($this->domain)) 
		{
			if ($forceBase === true)
			{
				$prefix = trim($this->basePrefix, '/')."/{$prefix}";
				$prefix = trim($prefix, '/');
			}
		}

		empty($prefix) and $prefix = '/';

		return $prefix;
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
