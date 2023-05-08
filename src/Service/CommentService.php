<?php

namespace App\Service;

use App\Entity\Comment;
use App\Repository\CommentRepository;
use DateTime;
use DateTimeImmutable;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class CommentService
{
    public function __construct(
        private CommentRepository $commentRepository,
        private Environment $environment
    ) {}

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function getCommentForPost(?string $user): array
    {
        $comments = $this->commentRepository->findCommentsLower3rdLevel();
        $sortedComments = $this->sortingComments($comments);
        $commentId3rdLevelWithThread = array_column($this->commentRepository->getCommentId3rdLevelWithReplies(), 'parentId');

        return ['commentTree' => $this->buildTree($sortedComments, $user, $commentId3rdLevelWithThread)];
    }

    public function saveNewComment(array $data, ?string $user): Comment
    {
        $comment = new Comment();
        if (isset($data['parentId'])) {
            $parentComment = $this->commentRepository->find($data['parentId']);
            $comment->setParentId($parentComment->getId())
                ->setRang($parentComment->getRang() + 1)
                ->setThirdLevelRoot($parentComment->getRang() === 3 ? $parentComment->getId() : $parentComment->getThirdLevelRoot());
        }
        $comment->setAuthor($user)
            ->setComment($data['comment']);

        $this->commentRepository->save($comment, true);

        return $comment;
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function getCommentsDeeperThenFour(int $thirdRootId, ?string $user): array
    {
        $comments = $this->commentRepository->findCommentsUpper3rdLevel($thirdRootId);
        $sortedComments = $this->sortingComments($comments, $thirdRootId);

        return ['commentTree' => $this->buildTree($sortedComments, $user)];
    }

    public function deleteComment(int $commentId, ?string $user): bool
    {
        $comment = $this->commentRepository->find($commentId);
        if ($comment->getAuthor() !== $user || $comment->getCreatedAt() < $this->getHourAgo()) {
            return false;
        }
        $comment->setComment("Комментарий удален пользователем");
        $comment->setDeletedAt(new DateTimeImmutable());
        $this->commentRepository->save($comment, true);
        return true;
    }

    public function updateComment(int $commentId, array $data, ?string $user): ?Comment
    {
        $comment = $this->commentRepository->find($commentId);
        if ($comment->getAuthor() !== $user || $comment->getCreatedAt() < $this->getHourAgo()) {
            return null;
        }
        $comment->setComment($data['comment']);
        $this->commentRepository->save($comment, true);
        return $comment;
    }

    private function sortingComments(array $comments, int $parentComment = null): array {
        $sortedComments = array_reverse(array_combine(array_column($comments,'id'), $comments), true);

        foreach ($sortedComments as &$comment) {
            if ($comment['parentId'] !== $parentComment) {
                if (!isset($sortedComments[$comment['parentId']])) {
                    $sortedComments[$comment['parentId']] = [];
                }
                $sortedComments[$comment['parentId']]['replies'][$comment['id']] = $comment;
            }
        }

        unset($comment);
        foreach ($sortedComments as $id => $comment) {
            if ($comment['parentId'] !== $parentComment) {
                unset($sortedComments[$id]);
            }
        }

        return $sortedComments;
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    private function buildTree(array $comments, ?string $user, array $commentId3rdLevelWithThread = null): string
    {
        $result = '';

        foreach ($comments as $comment) {
            if (isset($comment['replies'])) {
                $replies = $this->buildTree($comment['replies'], $user, $commentId3rdLevelWithThread);
            }
            $result = $this->environment->render("/comment/__comment.html.twig", [
                'comment' => $comment,
                'haveMore' => isset($commentId3rdLevelWithThread) && in_array($comment['id'], $commentId3rdLevelWithThread),
                'replies' => $replies ?? null,
                'canBeEdit' => $user === $comment['author'] && !isset($comment['deletedAt'])
            ]) . $result;
            unset($replies);
        }

        return $result;
    }

    private function getHourAgo(): DateTime
    {
        $hourAgo = date('Y-m-d H:i:s', strtotime(' -3 hours'));
        return DateTime::createFromFormat('Y-m-d H:i:s', $hourAgo);
    }
}
