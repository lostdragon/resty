<?php

/**
 * Redirect the end-user's user agent with error message.
 *
 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-20#section-4.1
 *
 * @ingroup oauth2_error
 */
class OAuth2_Exception_Redirect extends OAuth2_Exception_Server {

	/**
	 * @param string $redirect_uri
	 * An absolute URI to which the authorization server will redirect the
	 * user-agent to when the end-user authorization step is completed.
	 * @param string $error
	 * A single error code as described in Section 4.1.2.1
	 * @param $error_description
	 * (optional) A human-readable text providing additional information,
	 * used to assist in the understanding and resolution of the error
	 * occurred.
	 * @param string $state
	 * (optional) REQUIRED if the "state" parameter was present in the client
	 * authorization request. Set to the exact value received from the client.
	 *
	 * @param bool $is_fragment
	 *
	 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-20#section-4.1.2.1
	 *
	 * @ingroup oauth2_error
	 */
	public function __construct($redirect_uri, $error, $error_description = NULL, $state = NULL, $is_fragment = FALSE)
	{
		parent::__construct(Rest_Response::HTTP_FOUND, $error, $error_description);

		if ($state)
		{
			self::$error['state'] = $state;
		}

		if($is_fragment === TRUE) {
			$params = array('fragment' => self::$error);
		} else {
			$params = array('query' => self::$error);
		}

		$redirect = $this->buildUri($redirect_uri, $params);
		if ($redirect)
		{
			self::$header = array_merge(self::$header, array('Location' => $redirect));
		}
		return self::$header;
	}

	/**
	 * Build the absolute URI based on supplied URI and parameters.
	 *
	 * @param string $uri An absolute URI.
	 * @param array $params Parameters to be append as GET.
	 *
	 * @return string An absolute URI with supplied parameters.
	 *
	 * @ingroup oauth2_section_4
	 */
	protected function buildUri($uri, $params)
	{
		$parse_url = parse_url($uri);

		// Add our params to the parsed uri
		foreach ($params as $k => $v)
		{
			if (isset($parse_url[$k]))
			{
				$parse_url[$k] .= "&" . http_build_query($v);
			}
			else
			{
				$parse_url[$k] = http_build_query($v);
			}
		}

		// Put humpty dumpty back together
		return ((isset($parse_url["scheme"])) ? $parse_url["scheme"] . "://" : "") . ((isset($parse_url["user"])) ?
			$parse_url["user"] . ((isset($parse_url["pass"])) ? ":" . $parse_url["pass"] : "") . "@" : "") .
			((isset($parse_url["host"])) ? $parse_url["host"] : "") .
			((isset($parse_url["port"])) ? ":" . $parse_url["port"] : "") .
			((isset($parse_url["path"])) ? $parse_url["path"] : "") .
			((isset($parse_url["query"])) ? "?" . $parse_url["query"] : "") .
			((isset($parse_url["fragment"])) ? "#" . $parse_url["fragment"] : "");
	}
}