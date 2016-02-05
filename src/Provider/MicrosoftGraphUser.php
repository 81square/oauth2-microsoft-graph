<?php

namespace EightyOneSquare\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

/**
 * Representation of User resource
 * 
 * @link http://graph.microsoft.io/en-us/docs/api-reference/v1.0/resources/user User resource
 * @link http://graph.microsoft.io/en-us/docs/api-reference/beta/resources/user User resource (beta)
 */
class MicrosoftGraphUser implements ResourceOwnerInterface
{

    /**
     * Response data
     * 
     * @var array
     */
    protected $response;

    /**
     * Constructor
     * 
     * @param array $response Response data
     */
    public function __construct(array $response)
    {
        $this->response = $response;
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->getProperty('id');
    }

    /**
     * @inheritdoc
     */
    public function toArray()
    {
        return $this->response;
    }

    /**
     * Returns the name displayed in the address book for the user. This is 
     * usually the combination of the user's first name, middle initial and 
     * last name.
     * 
     * @return null|string displayName
     */
    public function getDisplayName()
    {
        return $this->getProperty('displayName');
    }

    /**
     * Returns email address (may be same as UserPrincipalName)
     * 
     * @return null|string mail
     */
    public function getEmail()
    {
        return $this->getProperty('mail');
    }

    /**
     * Returns the given name (first name) of the user.
     * 
     * @return null|string givenName
     */
    public function getFirstName()
    {
        return $this->getProperty('givenName');
    }

    /**
     * Returns the user's surname (family name or last name).
     * 
     * @return null|string surname
     */
    public function getLastName()
    {
        return $this->getProperty('surname');
    }

    /**
     * Returns the user principal name (UPN) of the user. This *should* map to 
     * the user's email name.
     * 
     * @return null|string userPrincipalName
     */
    public function getPrincipalName()
    {
        return $this->getProperty('userPrincipalName');
    }

    /**
     * Gets property value
     * 
     * @param string $property Property name
     * @param mixed $default Default value to return if property does not exist
     * @return mixed
     */
    public function getProperty($property, $default = null)
    {
        return isset($this->response[$property]) ? $this->response[$property] : $default;
    }
}
