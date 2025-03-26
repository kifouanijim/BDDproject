<?php

// src/Controller/ScraperController.php
// src/Controller/ScraperController.php

namespace App\Controller;

use App\Service\ScraperService;
use App\Entity\User;
use App\Entity\Category;
use App\Entity\Resource;  // Assurez-vous d'ajouter cette ligne pour utiliser l'entité Resource
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/scraper')]
class ScraperController extends AbstractController
{
    private ScraperService $scraperService;
    private EntityManagerInterface $entityManager;

    public function __construct(ScraperService $scraperService, EntityManagerInterface $entityManager)
    {
        $this->scraperService = $scraperService;
        $this->entityManager = $entityManager;
    }

    #[Route('/run', methods: ['GET'])]
    public function run(): JsonResponse
    {
        // Récupérer l'utilisateur connecté
        $user = $this->getUser(); // Cette méthode est fournie par Symfony pour obtenir l'utilisateur authentifié

        // Si l'utilisateur n'est pas authentifié, retourner une erreur
        if (!$user) {
            return $this->json(['error' => 'User not authenticated'], 401);
        }

        // Récupérer l'entité Resource à partir de la base de données
        $resource = $this->entityManager->getRepository(Resource::class)->find(1); // Remplacez l'ID selon votre logique

        // Vérifier si la ressource existe et si l'URL est définie
        if (!$resource || !$resource->getUrl()) {
            return $this->json(['error' => 'Resource or URL not found'], 404);
        }

        // Récupérer l'URL de la ressource
        $url = $resource->getUrl();  // Assurez-vous que `getUrl()` existe dans l'entité Resource

        // Récupérer une catégorie depuis la base de données
        $category = $this->entityManager->getRepository(Category::class)->find(1); // Remplacez par un ID valide ou logique de récupération

        // Scraper le site web
        try {
            $titles = $this->scraperService->scrapeWebsite($url, $user, $category);
            // Retourner une réponse JSON avec les titres extraits
            return $this->json(['scraped_titles' => $titles]);
        } catch (\Exception $e) {
            // En cas d'erreur, retournez l'erreur
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }
}
