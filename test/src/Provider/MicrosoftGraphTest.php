<?php

namespace EightyOneSquare\OAuth2\Client\Test\Provider;

use EightyOneSquare\OAuth2\Client\Provider\MicrosoftGraph as MicrosoftGraphProvider;
use Mockery as m;

class MicrosoftGraphTest extends \PHPUnit_Framework_TestCase
{

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

        $this->assertEquals('/common/oauth2/authorize', $uri['path']);
        
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
        $this->assertEquals('/common/oauth2/token', $uri['path']);
    }

    public function testGetSetApiUrlBase()
    {
        // Check default
        $this->assertEquals('https://graph.microsoft.com', $this->provider->getApiUrlBase());
        
        // Change without trailing
        $this->provider->setApiUrlBase('https://graph.windows.net');
        $this->assertEquals('https://graph.windows.net', $this->provider->getApiUrlBase());
        
        // Change with trailing
        $this->provider->setApiUrlBase('https://graph.windows.org/');
        $this->assertEquals('https://graph.windows.org', $this->provider->getApiUrlBase());
    }
    
    public function testGetSetApiVersion()
    {
        // Check default
        $this->assertEquals('v1.0', $this->provider->getApiVersion());
        
        // Change
        $this->provider->setApiVersion('beta');
        $this->assertEquals('beta', $this->provider->getApiVersion());
    }
    
    public function testGetSetLoginUrlBase()
    {
        // Check default
        $this->assertEquals('https://login.microsoftonline.com/common/oauth2', $this->provider->getLoginUrlBase());
        
        // Change without trailing
        $this->provider->setLoginUrlBase('https://login.microsoftonline.com/mytenant/oauth2');
        $this->assertEquals('https://login.microsoftonline.com/mytenant/oauth2', $this->provider->getLoginUrlBase());
        
        // Change with trailing
        $this->provider->setLoginUrlBase('https://login.microsoftonline.com/foo/oauth2/');
        $this->assertEquals('https://login.microsoftonline.com/foo/oauth2', $this->provider->getLoginUrlBase());
    }
    
    public function testResourceOwnerDetailsUrl()
    {
        $token = m::mock('League\OAuth2\Client\Token\AccessToken', [['access_token' => 'mock_access_token']]);
       
        // Expect: https://graph.microsoft.com/v1.0/me
        $url = $this->provider->getResourceOwnerDetailsUrl($token);
        $uri = parse_url($url);
       
        $this->assertEquals('/v1.0/me', $uri['path']);
        $this->assertNotContains('mock_access_token', $url);
    }
}
