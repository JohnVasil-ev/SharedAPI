<?php

namespace App\Model\Task;

use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

class UpdateTaskDTO
{
    public function __construct(
        #[Assert\Length(max: 255)]
        public ?string $title,
    
        public ?bool $completed,
    ) {}

}
