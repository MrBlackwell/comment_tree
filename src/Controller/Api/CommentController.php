<?php

namespace App\Controller\Api;

use App\Service\CommentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class CommentController extends AbstractController
{
    public function __construct(
        private CommentService $commentService
    ){}

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    #[Route('/comment', methods: ["GET"])]
    public function getCommentsList(Request $request): Response
    {
        $user = $request->cookies->get("username");
        return $this->json($this->commentService->getCommentForPost($user));
    }

    #[Route('/comment', name: 'app_api_comment', methods: ["POST"])]
    public function save(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        $user = $request->cookies->get("username");
        return $this->json(['comment' => $this->commentService->saveNewComment($data, $user)]);
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    #[Route("/{thirdLevelRoot}/deeper-comment", methods: ["GET"])]
    public function getCommentsDeeperThenFour(Request $request, int $thirdLevelRoot): Response
    {
        $user = $request->cookies->get("username");
        return $this->json($this->commentService->getCommentsDeeperThenFour($thirdLevelRoot, $user));
    }

    #[Route("/comment/{id}", methods: ["DELETE"])]
    public function delete(Request $request, int $id): JsonResponse
    {
        $user = $request->cookies->get("username");
        if ($this->commentService->deleteComment($id, $user)) {
            return $this->json([]);
        } else {
            return $this->json([], 403);
        }
    }

    #[Route("/comment/{id}", methods: ["PATCH"])]
    public function update(Request $request, int $id): JsonResponse
    {
        $user = $request->cookies->get("username");
        $data = json_decode($request->getContent(), true);
        $comment = $this->commentService->updateComment($id, $data, $user);
        if (isset($comment)) {
            return $this->json(["comment" => $comment]);
        } else {
            return $this->json([], 403);
        }
    }
}
