<?php

namespace Pressware\AwesomeSupport\API\Repository;

use stdClass;
use Pressware\AwesomeSupport\Constant\UserRoles;
use Pressware\AwesomeSupport\Entity\User;

class UserRepository extends Repository
{
    /**
     * Create a User model and then store into the repository.
     *
     * @since 0.1.0
     *
     * @param stdClass $user
     *
     * @return void
     * @throws InvalidArgumentException
     */
    public function createModel(stdClass $user)
    {
        if (!$user) {
            $this->notifier->setError(
                new InvalidArgumentException('No user passed to create a model')
            );
        }

        if ($this->has($user->id)) {
            return;
        }

        if (!property_exists($user, 'firstName')) {
            $user = $this->populateName($user);
        }
        if (empty($user->email)) {
            $user->email = $user->id.'@unknown.com';
        }

        $model = new User(
            $user->email,
            $user->firstName,
            $user->lastName,
            $this->getUserRole($user->role)
        );

        if (isset($user->tags)) {
            $model->setTags($user->tags);
        }

        $this->set($user->id, $model);
    }

    /**
     * Create a user data model object and return it. It is not stored in the repository.
     *
     * @since 0.1.0
     *
     * @param int $userId
     * @param string $name
     * @param string $email
     * @param string $role
     *
     * @return object|void
     */
    public function create($userId, $name, $email, $role)
    {
        if ($this->has($userId)) {
            return;
        }

        list($firstName, $lastName) = $this->getName($name);

        return (object)[
            'id'        => $userId,
            'name'      => $name,
            'firstName' => $firstName,
            'lastName'  => $lastName,
            'email'     => $email,
            'role'      => $role,
        ];
    }

    /*******************
     * Helpers
     ******************/

    /**
     * Parse the full name into first and surname.
     *
     * @since 0.1.0
     *
     * @param string $fullName
     *
     * @return array
     */
    protected function getName($fullName)
    {
        $name    = explode(' ', $fullName, 2);
        $surname = count($name) > 1 ? $name[1] : '';
        return [$name[0], $surname];
    }

    /**
     * Populate the name properties by splitting the full name into first and last name.
     *
     * @since 0.1.0
     *
     * @param \stdClass $user
     *
     * @return object
     */
    private function populateName(\stdClass $user)
    {
        $userArray = (array)$user;
        list($userArray['firstName'], $userArray['lastName']) = $this->getName($user->name);
        return (object)$userArray;
    }

    /**
     * Get the user's role.
     *
     * @since 0.1.0
     *
     * @param $userRole
     *
     * @return string
     */
    protected function getUserRole($userRole)
    {
        switch ($userRole) {
            case 'user':
            case 'customer':
            case 'end-user':
                return UserRoles::CUSTOMER;
            case 'agent':
            case 'admin':
            case 'employee':
                return UserRoles::AGENT;
        }
    }
}
