<?php

namespace App\DataFixtures;

use App\Entity\Comment;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class CommentFixtures extends Fixture
{
    public const COMMENT_COUNT_ON_ONE_LEVEL = 20;

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        for ($i = 0; $i < 6; $i++) { // for six level of comments
            $comments = [];
            for ($j = 0; $j < self::COMMENT_COUNT_ON_ONE_LEVEL; $j++) {

                $comment = new Comment();
                $comment->setAuthor($faker->userName)
                    ->setComment($faker->realText())
                    ->setRang($i + 1);
                if (isset($parentIds)) {
                    $parentComment = $faker->randomElement($parentIds);
                    $comment->setParentId($parentComment['parent_id']);
                    if ($i === 3 ) {
                        $comment->setThirdLevelRoot($parentComment['parent_id']);
                    } else {
                        $comment->setThirdLevelRoot($parentComment['thirdLevelRoot']);
                    }
                }
                $manager->persist($comment);
                $comments[] = $comment;
            }
            $manager->flush();
            $parentIds = array_map(fn($item) => [
                'parent_id' => $item->getId(),
                'thirdLevelRoot' => $item->getThirdLevelRoot()
            ], $comments);
        }
    }
}
