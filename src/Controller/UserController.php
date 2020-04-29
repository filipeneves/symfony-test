<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * PresenceController constructor.
     * @param UserRepository $userRepository
     */
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * @Route("/users", name="get_users", methods={"GET"})
     * @return JsonResponse
     */
    public function read(): JsonResponse
    {
        $users = $this->userRepository->findAll();

        // This solution with symfony is not very elegant, should promote the use of Transformers
        $output = [];
        foreach ($users as $user) {
            $output[] = [
                'id' => $user->getId(),
                'firstName' => $user->getFirstName(),
                'familyName' => $user->getFamilyName(),
                'email' => $user->getEmail()
            ];
        }

        return new JsonResponse($output, Response::HTTP_OK);
    }

}
