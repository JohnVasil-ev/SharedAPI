<?php

namespace App\Model\Task;

use Symfony\Component\Validator\Constraints as Assert;

class CreateTaskDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $title,
    
        public bool $completed = false,
    ) {}
}
