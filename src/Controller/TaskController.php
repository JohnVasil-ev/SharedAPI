<?php

namespace App\Controller;

use App\Entity\Task;
use App\Model\Task\CreateTaskDTO;
use App\Model\Task\UpdateTaskDTO;
use App\Model\Task\UpdateTasksDTO;
use App\Trait\StatusCode;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use stdClass;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Serializer\SerializerInterface;

class TaskController extends ApplicationController
{
    #[Route('/tasks', name: 'app_task_index', format: 'json', methods: ['GET'])]
    public function getTasks(EntityManagerInterface $entityManager, SerializerInterface $serializer): Response
    {
        $tasks = $entityManager->getRepository(Task::class)->findBy([], ['created_at' => 'ASC']);

        $serialized_tasks = $serializer->serialize($tasks, 'json', [
            'groups' => [],
        ]);

        return $this->response(StatusCode::OK, json_decode($serialized_tasks));
    }

    #[Route('/tasks/{id}', name: 'app_task_show', methods: ['GET'])]
    public function getTaskById(EntityManagerInterface $entityManager, string $id): Response
    {
        $task = $entityManager->getRepository(Task::class)->find($id);
        if (!$task) {
            return $this->response(StatusCode::NOT_FOUND);
        }

        return $this->response(StatusCode::OK, $task);
    }

    #[Route('/tasks', name: 'app_task_create', methods: ['POST'])]
    public function createTask(
        Request $request,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        #[MapRequestPayload] CreateTaskDTO $taskDTO
    ): Response
    {
        $task = new Task();

        $task->setTitle($taskDTO->title);
        $task->setCompleted($taskDTO->completed);

        $errors = $validator->validate($task);
        if (count($errors) > 0) {
            return $this->response(StatusCode::BAD_REQUEST, (string) $errors);
        }

        $entityManager->persist($task);
        $entityManager->flush();

        return $this->response(StatusCode::CREATED, $task);
    }

    #[Route('/tasks/{id}', name: 'app_task_update', format: 'json', methods: ['PATCH'])]
    public function updateTask(
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        #[MapRequestPayload] UpdateTaskDTO $taskDTO,
        string $id
    ): Response
    {
        $task = $entityManager->getRepository(Task::class)->find($id);
        if (!$task) {
            return $this->response(StatusCode::NOT_FOUND);
        }

        if ($taskDTO->title !== null) {
            $task->setTitle($taskDTO->title);
        }
        if ($taskDTO->completed !== null) {
            $task->setCompleted($taskDTO->completed);
        }

        $errors = $validator->validate($task);
        if (count($errors) > 0) {
            return $this->response(StatusCode::BAD_REQUEST, (string) $errors);
        }

        $entityManager->persist($task);
        $entityManager->flush();

        return $this->response(StatusCode::OK, $task);
    }

    #[Route('/tasks', name: 'app_tasks_update', format: 'json', methods: ['PUT'])]
    public function updateTasks(
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        LoggerInterface $logger,
        Request $request,
    ): Response
    {
        $tasksDTO = array_map(function (stdClass $taskDTO) use ($serializer, $logger) {
            $serialized_tasks = $serializer->deserialize(json_encode($taskDTO), UpdateTasksDTO::class, 'json');
            return array_filter((array)$serialized_tasks, fn ($value) => !is_null($value));
        }, json_decode($request->getContent()));

        $tasks_ids = array_column($tasksDTO, 'id');
        $tasks = $entityManager->getRepository(Task::class)->findBy(['id' => $tasks_ids]);

        if (empty($tasks)) {
            return $this->response(StatusCode::NOT_FOUND);
        }

        $entityManager->getConnection()->beginTransaction();

        try {
            foreach ($tasks as $task) {
                $obj = array_values(array_filter($tasksDTO, function ($taskDTO) use ($task) {
                    return $task->getId() == $taskDTO['id'];
                }))[0];
    
                if (!$obj) {
                    continue;
                }
    
                if (array_key_exists('title', $obj) and $obj['title'] !== null) {
                    $task->setTitle($obj['title']);
                }
                if (array_key_exists('completed', $obj) and $obj['completed'] !== null) {
                    $task->setCompleted($obj['completed']);
                }
    
                $errors = $validator->validate($task);
                if (count($errors) > 0) {
                    return $this->response(StatusCode::BAD_REQUEST, (string) $errors);
                }
    
                $entityManager->persist($task);
                $entityManager->flush();
            }

            $entityManager->getConnection()->commit();

            return $this->response(StatusCode::OK, $tasks);
        } catch (Exception $e) {
            $entityManager->getConnection()->rollBack();

            return $this->response(StatusCode::BAD_REQUEST, $e->getMessage());
        }
    }
}
