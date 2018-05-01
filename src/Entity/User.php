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
		
		// Make sure first name is not empty since WP does not like it
		if ( empty( $this->firstName ) ) {
			$this->firstName = 'None';
		}
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
