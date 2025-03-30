<?php
// src/DataFixtures/CategoryFixtures.php
namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Category;

class CategoryFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Liste des catégories codées en dur
        $categories = [
            'Actualités',
            'Technologie',
            'Science',
            'Divertissement',
            'Éducation',
            'Santé',
            'Sport',
            'Finance',
        ];

        foreach ($categories as $categoryName) {
            $category = new Category();
            $category->setName($categoryName);
            $manager->persist($category);
        }

        // Exécuter l'enregistrement en base de données
        $manager->flush();
    }
}
