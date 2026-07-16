<?php

namespace App\DataFixtures;

use App\Entity\FavoriteItem;
use App\Entity\FavoriteList;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // No real auth exists (see FavoritesService::getCurrentUser()) — this
        // is the single demo user every request is treated as being logged in as.
        $user = new User('demo@example.com', 'not-a-real-password');
        $manager->persist($user);

        $list = new FavoriteList('My Favorites', $user);
        $manager->persist($list);

        $manager->persist(new FavoriteItem(
            'tt0133093',
            'The Matrix',
            'https://m.media-amazon.com/images/M/MV5BN2NmN2VhMTQtMDNiOS00NDlhLTliMjgtODE2ZTY0ODQyNDRhXkEyXkFqcGc@._V1_QL75_UX380_CR0,4,380,562_.jpg',
            1999,
            $list,
        ));

        $manager->persist(new FavoriteItem(
            'tt1375666',
            'Inception',
            'https://m.media-amazon.com/images/M/MV5BMjAxMzY3NjcxNF5BMl5BanBnXkFtZTcwNTI5OTM0Mw@@._V1_QL75_UX380_CR0,0,380,562_.jpg',
            2010,
            $list,
        ));

        $manager->flush();
    }
}
