<?php

namespace App\Controller;

use App\Repository\PresenceRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PresenceController extends AbstractController
{

    /**
     * @var PresenceRepository
     */
    private $presenceRepository;

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * PresenceController constructor.
     * @param PresenceRepository $presenceRepository
     * @param UserRepository $userRepository
     */
    public function __construct(PresenceRepository $presenceRepository, UserRepository $userRepository)
    {
        $this->presenceRepository = $presenceRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * @Route("/presence/{id}", name="get_presence", methods={"GET"})
     * @param $id
     * @return JsonResponse
     */
    public function read($id): JsonResponse
    {
        $userPresence = $this->presenceRepository->findBy(['userId' => $id]);

        // This solution with symfony is not very elegant, should promote the use of Transformers
        $output = [];
        foreach ($userPresence as $user) {
            $output[] = [
                'id' => $user->getId(),
                'user_id' => $user->getUserId(),
                'date_time' => $user->getDateTime()->format('Y-m-d H:i:s'),
                'type' => $user->getType()
            ];
        }

        return new JsonResponse($output, Response::HTTP_OK);
    }

    /**
     * @Route("/presence/check-in", name="add_presence_entry", methods={"POST"})
     * @param Request $request
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $user = $this->userRepository->find($data['id']);

        // Checks if employee exists or ID is not part of the request
        if (!$user || !isset($data['id'])) {
            return new JsonResponse(['response' => 'employee not found or id not provided'], Response::HTTP_NOT_FOUND);
        }

        $entries = $this->presenceRepository->findBy(['userId' => $data['id']]);

        // This is to make sure that even if the user doesn't have check ins, he can get an entry the first time.
        if (empty($entries)) {
            $isEntry = true;
        }
        else {
            $isEntry = end($entries)->getType() === 'out' ? true : false;
        }

        if ($this->presenceRepository->checkIn($data['id'], $isEntry)) {
            if ($isEntry) {
                $output = ['response' => $user->getFullName() . " welcome!"];
            } else {
                $entry = end($entries)->getDateTime();
                $now = \DateTime::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s'));
                $timeDiff = $now->diff($entry);
                $timePassed = $timeDiff->format('%hh') . $timeDiff->format('%im') . $timeDiff->format('%ss');
                $output = ['response' => $user->getFullName() . " exit", 'time_passed' => $timePassed];
            }
            return new JsonResponse($output, Response::HTTP_CREATED);
        }

        return new JsonResponse(['response' => 'could not save employee check-in'], Response::HTTP_BAD_REQUEST);
    }

    /**
     * @Route("/presence/edit/{id}", name="update_presence", methods={"PUT"})
     * @param $id
     * @param Request $request
     * @return JsonResponse
     */
    public function update($id, Request $request): JsonResponse
    {
        $presence = $this->presenceRepository->find(['id' => $id]);
        $data = json_decode($request->getContent(), true);
        $presence->setDateTime(\DateTime::createFromFormat('Y-m-d H:i:s', $data['date_time']));
        if ($this->presenceRepository->updateDateTime($presence)) {
            return new JsonResponse(['response' => 'updated successfully'], Response::HTTP_OK);
        }
        return new JsonResponse(['response' => 'could not update'], Response::HTTP_BAD_REQUEST);
    }

    /**
     * @Route("/presence/remove/{id}", name="remove_presence", methods={"DELETE"})
     * @param $id
     * @return JsonResponse
     */
    public function delete($id): JsonResponse
    {
        $presence = $this->presenceRepository->find(['id' => $id]);
        if ($this->presenceRepository->deleteEntry($presence)) {
            return new JsonResponse(['response' => 'removed successfully'], Response::HTTP_OK);
        }
        return new JsonResponse(['response' => 'could not remove'], Response::HTTP_BAD_REQUEST);
    }

}
