<?php

// src/Controller/ScraperController.php
namespace App\Controller;

use App\Service\ScraperService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/scraper')]
class ScraperController extends AbstractController
{
    private ScraperService $scraperService;

    public function __construct(ScraperService $scraperService)
    {
        $this->scraperService = $scraperService;
    }

    #[Route('/run', methods: ['GET'])]
    public function run(): JsonResponse
    {
        // Scrape the website
        $titles = $this->scraperService->scrapeWebsite("https://example.com");

        // Return a JSON response with the scraped titles
        return $this->json(['scraped_titles' => $titles]);
    }
}