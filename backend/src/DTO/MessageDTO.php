<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class MessageDTO
 *
 * Data transfer object for message properties validation
 *
 * @package App\DTO
 */
class MessageDTO
{
    #[Assert\NotBlank(message: 'The message value should not be blank.')]
    #[Assert\Length(
        min: 1,
        max: 2000,
        minMessage: 'The message value must have at least {{ limit }} characters.',
        maxMessage: 'The message value must have at most {{ limit }} characters.'
    )]
    public string $message;
}
