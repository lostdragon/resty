<?php

/**
 * OAuth2 errors that require termination of OAuth2 due to
 * an error.
 *
 */
class OAuth2_Exception_Server extends Rest_Exception {

	/**
	 * @param $http_status_code
	 * HTTP status code message as predefined.
	 * @param $error
	 * A single error code.
	 * @param $error_description
	 * (optional) A human-readable text providing additional information,
	 * used to assist in the understanding and resolution of the error
	 * occurred.
	 */
	public function __construct($http_status_code, $error, $error_description = NULL)
	{
		parent::__construct($http_status_code, $error);

		if($error_description) {
			self::$error['error_description'] = $error_description;
		}

		/**
		 * Send out HTTP headers for JSON.
		 *
		 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-20#section-5.1
		 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-20#section-5.2
		 *
		 * @ingroup oauth2_section_5
		 */
		self::$header = array_merge(self::$header, array('Cache-Control' => 'no-store', 'Pragma' => 'no-cache'));
	}
}