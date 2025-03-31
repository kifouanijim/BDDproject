<?php

namespace App\Controller;

use App\Service\ScraperService;
use App\Entity\User;
use App\Entity\Category;
use App\Entity\Resource;
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
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'User not authenticated'], 401);
        }

        // Récupérer toutes les ressources avec des URLs définies
        $resources = $this->entityManager->getRepository(Resource::class)->findAll();

        if (empty($resources)) {
            return $this->json(['error' => 'No resources found'], 404);
        }

        $scrapedData = [];

        foreach ($resources as $resource) {
            if (!$resource->getUrl()) {
                continue; // Passer les ressources sans URL
            }

            $category = $resource->getCategory(); // Récupérer la catégorie associée

            try {
                $titles = $this->scraperService->scrapeWebsite($resource->getUrl(), $user, $category);
                $scrapedData[] = [
                    'resource_id' => $resource->getId(),
                    'url' => $resource->getUrl(),
                    'scraped_titles' => $titles,
                ];
            } catch (\Exception $e) {
                // Enregistrer les erreurs par URL
                $scrapedData[] = [
                    'resource_id' => $resource->getId(),
                    'url' => $resource->getUrl(),
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $this->json(['scraped_results' => $scrapedData]);
    }
}
