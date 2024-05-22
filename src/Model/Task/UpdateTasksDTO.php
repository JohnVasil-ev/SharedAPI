<?php

namespace App\Model\Task;

use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

class UpdateTasksDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public Uuid $id,

        #[Assert\Length(max: 255)]
        public ?string $title,
    
        public ?bool $completed,
    ) {}

}
