<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class UserDTO
 *
 * Data transfer object for user properties validation
 *
 * @package App\DTO
 */
class UserDTO
{
    #[Assert\NotBlank(message: 'username value should not be blank.')]
    public string $username;

    #[Assert\NotBlank(message: 'password value should not be blank.')]
    #[Assert\Length(
        min: 6,
        max: 128,
        minMessage: 'password value should have at least {{ limit }} characters.',
        maxMessage: 'password value should have at most {{ limit }} characters.'
    )]
    public string $password;
}
