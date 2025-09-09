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
    #[Assert\NotBlank(message: "email value should not be blank.")]
    #[Assert\Email(message: "email value is not a valid email address.")]
    public string $email;

    #[Assert\NotBlank(message: "first-name value should not be blank.")]
    #[Assert\Length(
        min: 2,
        max: 80,
        minMessage: "first-name value should have at least {{ limit }} characters.",
        maxMessage: "first-name value should have at most {{ limit }} characters."
    )]
    public string $firstName;

    #[Assert\NotBlank(message: "last-name value should not be blank.")]
    #[Assert\Length(
        min: 2,
        max: 80,
        minMessage: "last-name value should have at least {{ limit }} characters.",
        maxMessage: "last-name value should have at most {{ limit }} characters."
    )]
    public string $lastName;

    #[Assert\NotBlank(message: "password value should not be blank.")]
    #[Assert\Length(
        min: 6,
        max: 128,
        minMessage: "password value should have at least {{ limit }} characters.",
        maxMessage: "password value should have at most {{ limit }} characters."
    )]
    public string $password;
}
