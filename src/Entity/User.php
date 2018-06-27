<?php

namespace Pressware\AwesomeSupport\Entity;

use Pressware\AwesomeSupport\Constant\UserRoles;

class User
{
    /**
     * @var string
     */
    private $email;

    /**
     * @var string
     */
    private $firstName;

    /**
     * @var string
     */
    private $lastName;

    /**
     * @var string
     */
    private $role;

    /**
     * @var array
     */
    private $tags;

    /**
     * User constructor.
     *
     * @param string $role
     * @param string $email
     * @param string $firstName
     * @param string $lastName
     */
    public function __construct($email, $firstName, $lastName, $role = UserRoles::CUSTOMER)
    {
        $this->email     = $email;
        $this->firstName = $firstName;
        $this->lastName  = $lastName;
        $this->role      = $role;
    }

    /**
     * @tags array
     */
    public function setTags($tags) {
        $this->tags = $tags;
    }

    /**
     * @return string
     */
    public function getTags() {
        return $this->tags;
    }
    /**
     * @return string
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }
}
