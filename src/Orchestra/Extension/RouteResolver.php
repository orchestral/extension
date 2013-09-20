<?php namespace Orchestra\Extension;

class RouteResolver {

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
	 * Construct a new instance.
	 *
	 * @param  string   $handles
	 */
	public function __construct($handles, $baseUrl = null, $secure = false)
	{
		$this->secure  = $secure;
		$this->baseUrl = str_replace(array('https://', 'http://'), '', $baseUrl);

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
		$domain = "{$http}://".trim($this->domain(), '/');
		$prefix = trim($this->prefix, '/');

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
	public function domain()
	{
		$domain = $this->domain;
		
		is_null($domain) and $domain = $this->baseUrl;

		return $domain;
	}

	/**
	 * Get route prefix.
	 *
	 * @return string
	 */
	public function prefix()
	{
		return $this->prefix;
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
