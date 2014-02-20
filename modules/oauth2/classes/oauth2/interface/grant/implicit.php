<?php

/**
 * Storage engines that support the "Implicit"
 * grant type should implement this interface
 * 
 * @author Dave Rochwerger <catch.dave@gmail.com>
 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-20#section-4.2
 */
interface OAuth2_Interface_Grant_Implicit extends OAuth2_Interface_Storage {
	
	/**
	 * The Implicit grant type supports a response type of "token". 
	 * 
	 * @var string
	 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-20#section-1.4.2
	 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-20#section-4.2
	 */
	const RESPONSE_TYPE_TOKEN = OAuth2::RESPONSE_TYPE_ACCESS_TOKEN;
}