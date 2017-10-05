<?php

namespace EightyOneSquare\OAuth2\Client\Test\Provider;

use EightyOneSquare\OAuth2\Client\Provider\MicrosoftGraph as MicrosoftGraphProvider;
use Mockery as m;

class MicrosoftGraphTest extends \PHPUnit_Framework_TestCase
{

	/**
	 * @var MicrosoftGraphProvider
	 */
    protected $provider;

    protected function setUp()
    {
        $this->provider = new MicrosoftGraphProvider([
            'clientId' => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri' => 'none',
        ]);
    }

    public function tearDown()
    {
        m::close();
        parent::tearDown();
    }

    public function testAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);

        $this->assertEquals('/common/oauth2/v2.0/authorize', $uri['path']);

        $query = array();
        parse_str($uri['query'], $query);
        $this->assertArrayHasKey('client_id', $query);
        $this->assertArrayHasKey('redirect_uri', $query);
        $this->assertArrayHasKey('state', $query);
        $this->assertArrayHasKey('scope', $query);
        $this->assertArrayHasKey('response_type', $query);
        $this->assertArrayHasKey('approval_prompt', $query);
        $this->assertNotNull($this->provider->getState());
    }

    public function testBaseAccessTokenUrl()
    {
        $url = $this->provider->getBaseAccessTokenUrl([]);
        $uri = parse_url($url);
        $this->assertEquals('/common/oauth2/v2.0/token', $uri['path']);
    }

    public function testGetSetApiVersion()
    {
        // Check default
        $this->assertEquals('v1.0', $this->provider->getApiVersion());

        // Change
        $this->provider->setApiVersion('beta');
        $this->assertEquals('beta', $this->provider->getApiVersion());
    }

    public function testGetBaseAuthorizationUrl()
    {
        // Check default
        $this->assertEquals('https://login.microsoftonline.com/common/oauth2/v2.0/authorize', $this->provider->getBaseAuthorizationUrl());

        // Change tenant
        $this->provider->setTenant('contoso.onmicrosoft.com');
        $this->assertEquals('https://login.microsoftonline.com/contoso.onmicrosoft.com/oauth2/v2.0/authorize', $this->provider->getBaseAuthorizationUrl());
    }
    
    public function testGetBaseAccessTokenUrl()
    {
        // Check default
        $this->assertEquals('https://login.microsoftonline.com/common/oauth2/v2.0/token', $this->provider->getBaseAccessTokenUrl([]));

        // Change tenant
        $this->provider->setTenant('contoso.onmicrosoft.com');
        $this->assertEquals('https://login.microsoftonline.com/contoso.onmicrosoft.com/oauth2/v2.0/token', $this->provider->getBaseAccessTokenUrl([]));
    }

    public function testConstruct_OAuth2Path() {
        // Change with different oauth2 path (constructor)
        $provider = new MicrosoftGraphProvider([
            'clientId' => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri' => 'none',
            'pathOAuth2' => '/oauth2/v2.0',
        ]);
        
        $this->assertEquals('https://login.microsoftonline.com/common/oauth2/v2.0/authorize', $provider->getBaseAuthorizationUrl());
        $this->assertEquals('https://login.microsoftonline.com/common/oauth2/v2.0/token', $provider->getBaseAccessTokenUrl([]));
    }

    public function testResourceOwnerDetailsUrl()
    {
        $token = m::mock('League\OAuth2\Client\Token\AccessToken', [['access_token' => 'mock_access_token']]);

        // Expect: https://graph.microsoft.com/v1.0/me
        $url = $this->provider->getResourceOwnerDetailsUrl($token);
        $urlParsed = parse_url($url);

        $this->assertEquals('/v1.0/me', $urlParsed['path']);
        $this->assertNotContains('mock_access_token', $url);
        
        // Change api version
        $this->provider->setApiVersion('beta');
        
        // Expect: https://graph.microsoft.com/beta/me
        $urlBeta = $this->provider->getResourceOwnerDetailsUrl($token);
        $urlBetaParsed = parse_url($urlBeta);

        $this->assertEquals('/beta/me', $urlBetaParsed['path']);
        $this->assertNotContains('mock_access_token', $urlBeta);
    }
}
