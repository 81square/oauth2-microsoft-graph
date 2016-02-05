<?php

namespace EightyOneSquare\OAuth2\Client\Test\Provider;

use EightyOneSquare\OAuth2\Client\Provider\MicrosoftGraphUser;

class MicrosoftGraphUserTest extends \PHPUnit_Framework_TestCase
{

    private $mockResponse = array(
        'id' => 'aabbccddee99',
        'displayName' => 'John Doe',
        'givenName' => 'John',
        'jobTitle' => 'Wannabe Boss',
        'mail' => 'john@contoso.onmicrosoft.com',
        'surname' => 'Doe',
        'userPrincipalName' => 'john23314@contoso.com',
        'proxyAddresses' => [
            'smtp:john23314@contoso.com',
            'SMTP: vera@doe.com',
        ],
    );

    public function testBasicProperties()
    {
        $usr = new MicrosoftGraphUser($this->mockResponse);

        $this->assertEquals('aabbccddee99', $usr->getId());

        $this->assertEquals('John Doe', $usr->getDisplayName());
        $this->assertEquals('john@contoso.onmicrosoft.com', $usr->getEmail());
        $this->assertEquals('John', $usr->getFirstName());
        $this->assertEquals('Doe', $usr->getLastName());
        $this->assertEquals('john23314@contoso.com', $usr->getPrincipalName());
    }
    
    public function testToArray()
    {
        $usr = new MicrosoftGraphUser($this->mockResponse);

        $this->assertEquals($this->mockResponse, $usr->toArray());
    }
    
    public function testGetProperty()
    {
        $usr = new MicrosoftGraphUser($this->mockResponse);

        $this->assertEquals('aabbccddee99', $usr->getProperty('id'));
        $this->assertEquals('aabbccddee99', $usr->getProperty('id', 'fallback'));
        
        $this->assertNull($usr->getProperty('missing'));
        $this->assertEquals('fallback', $usr->getProperty('missing', 'fallback'));
        
        // Test array return
        $expectProxyAddresses = [
            'smtp:john23314@contoso.com',
            'SMTP: vera@doe.com',
        ];
        $this->assertEquals($expectProxyAddresses, $usr->getProperty('proxyAddresses'));
    }
}
