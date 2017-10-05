<?php

namespace EightyOneSquare\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

/**
 * Provider for Microsoft Graph API
 */
class MicrosoftGraph extends AbstractProvider
{

    use BearerAuthorizationTrait;

    const ACCESS_TOKEN_RESOURCE_OWNER_ID = 'id';
    const ACCESS_TOKEN_RESOURCE = 'https://graph.microsoft.com/';
    const DEFAULT_SCOPES = ['openid', 'profile', 'offline_access'];

    /**
     * Base URL for Microsoft Graph API requests
     * 
     * @var string URL
     */
    protected $apiUrlBase = 'https://graph.microsoft.com';

    /**
     * API Version string. Used for building api url.
     * 
     * @var string API version like 'v1.0' or 'beta'.
     */
    protected $apiVersion = 'v1.0';

    /**
     * Base URL for Authorization (OAuth2 endpoint)
     * 
     * @var string URL
     * @link http://graph.microsoft.io/en-us/docs/authorization/app_authorization Graph Authorization
     */
    protected $loginUrlBase = 'https://login.microsoftonline.com';

    /**
     * Tentant ID (used in login URL)
     * 
     * @var string Tenant ID 
     */
    protected $tenant = 'common';
    
    /**
     * OAuth2 path (used in login url)
     * 
     * You may want to set this to /oauth2/v2 when using the v2.0 app model
     * 
     * @var string path
     */
    protected $pathOAuth2 = '/oauth2/v2.0';

    /**
     * Gets base URL
     * 
     * @return string Base URL
     */
    public function getApiUrlBase()
    {
        return $this->apiUrlBase;
    }

    /**
     * Sets API version string.
     * 
     * @param string $apiUrlBase Base URL
     * @return self
     */
    public function setApiUrlBase($apiUrlBase)
    {
        // Remove trailing slash
        $this->apiUrlBase = rtrim($apiUrlBase, '/');
        return $this;
    }

    /**
     * Gets API version string.
     * 
     * @return string API version like 'v1.0' or 'beta'.
     */
    public function getApiVersion()
    {
        return $this->apiVersion;
    }

    /**
     * Sets API version string.
     * 
     * @param string $apiVersion API version like 'v1.0' or 'beta'.
     * @return self
     * @throws \InvalidArgumentException On non-string value
     */
    public function setApiVersion($apiVersion)
    {
        // Ensure string.
        if (!is_string($apiVersion)) {
            throw new \InvalidArgumentException('apiVersion should be string value!');
        }

        $this->apiVersion = $apiVersion;
        return $this;
    }

    /**
     * Gets tenant (default: common)
     * 
     * @return string tenant ID
     */
    public function getTenant()
    {
        return $this->tenant;
    }
    
    /**
     * Sets tenant (default: common)
     * 
     * @param string $tenant ID
     */
    public function setTenant($tenant)
    {
        $this->tenant = $tenant;
    }
    
    /**
     * @inheritdoc
     */
    public function getBaseAuthorizationUrl()
    {
        // examples: https://login.microsoftonline.com/common/oauth2/authorize
        return $this->loginUrlBase . '/'. $this->tenant . $this->pathOAuth2 . '/authorize';
    }

    /**
     * @inheritdoc
     */
    public function getBaseAccessTokenUrl(array $params)
    {
        // example: https://login.microsoftonline.com/common/oauth2/token
        return $this->loginUrlBase .'/'. $this->tenant . $this->pathOAuth2 . '/token';
    }

    /**
     * @inheritdoc
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        // example: https://graph.microsoft.com/v1.0/me
        return $this->apiUrlBase . '/' . $this->apiVersion . '/me';
    }


    /**
     * @inheritdoc
     */
    protected function getDefaultScopes()
    {
	    return self::DEFAULT_SCOPES;
    }

	/**
	 * @inheritdoc
	 */
	protected function getScopeSeparator(){
		return ' ';
	}

    /**
     * @inheritdoc
     */
    public function getAccessToken($grant = 'authorization_code', array $params = [])
    {
        return parent::getAccessToken($grant, $params);
    }

    /**
     * @inheritdoc
     */
    protected function checkResponse(ResponseInterface $response, $data)
    {
        if (isset($data['odata.error'])) {
            // OData error?
            $this->handleODataError($response, $data);
            return;
        }

        if (isset($data['error'])) {
            if (is_array($data['error'])) {
                $this->handleApiError($response, $data);
                return;
            }
            
            // Probably OAuth2 error
            $this->handleOAuth2Error($response, $data);
            return;
        }
        
        // No errors
    }

    /**
     * @inheritdoc
     */
    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new MicrosoftGraphUser($response);
    }

    /**
     * Handle oauth2 error
     * 
     * @param ResponseInterface $response Response
     * @param array $data Error response data
     * @throws IdentityProviderException
     */
    protected function handleOAuth2Error(ResponseInterface $response, $data)
    {
        /*
         * Example error in OAuth2 authorization process:
         *  array(
         *      'error' => 'invalid_grant', 
         *      'error_description' => 'AADSTS65001: The user or administrator has not consented to use the application with ID 'xxxxxxxxx77'. Send an interactive authorization request for this user and resource. ', 
         *      'error_codes' => array('65001'), 
         *      'timestamp' => '2016-02-08 17:12:11Z', 
         *      'trace_id' => 'xxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxx', 
         *      'correlation_id' => 'xxx...', 
         *      'resource_owner_id' => null
         * )
         */

        if (!empty($data['error'])) {
            $message = $data['error'] . ': ' . $data['error_description'];
            throw new IdentityProviderException($message, $data['error_codes'][0], $data);
        }
    }

    /**
     * Handle OData error
     * 
     * @param ResponseInterface $response Response
     * @param array $data Error response data
     * @throws IdentityProviderException
     */
    protected function handleODataError(ResponseInterface $response, $data)
    {
        if (isset($data['odata.error']['message'])) {
            $message = $data['odata.error']['message']['value'];
        } else {
            $message = $response->getReasonPhrase();
        }

        // Throw
        throw new IdentityProviderException($message, $response->getStatusCode(), $response);
    }
    
    /**
     * Handle API error
     * 
     * @param ResponseInterface $response  Response
     * @param array $data Error response data
     * @throws IdentityProviderException
     */
    protected function handleApiError(ResponseInterface $response, $data)
    {
        /*
         * Example of API error:
         * 
         * {
         *   "error": {
         *     "code": "ErrorNonExistentMailbox",
         *     "message": "The SMTP address has no mailbox associated with it."
         *   }
         * }
         * 
         */
     
        if (is_array($data['error']) && isset($data['error']['code'])) {
            $message = $data['error']['code'] . ': ' . $data['error']['message'];
            throw new IdentityProviderException($message, $response->getStatusCode(), $data);
        }
    }
    
    public function getMemberOf($token, array $options = [])
    {
        $response = $this->sendGet('/me/memberOf/$/microsoft.graph.group', $token, $options);
        return $response;
    }
    
    public function sendGet($endpoint, $token, array $options = [])
    {
        // Build URL
        // example: https://graph.microsoft.com/v1.0/me/messages
        $url = $endpoint;
        if (strpos($endpoint, '/') === 0) {
            $url = $this->apiUrlBase . '/' . $this->apiVersion . $endpoint;
        }
        
        $request = $this->getAuthenticatedRequest('GET', $url, $token, $options);
        $response = $this->getResponse($request);
        return $response;
    }
}
