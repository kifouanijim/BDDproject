<?php
// src/Service/ScraperService.php

namespace App\Service;

use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\ScrapedData; // Importez ScrapedData
use App\Entity\User;
use App\Entity\Category;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\DomCrawler\Crawler;

class ScraperService
{
    private $client;
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->client = new HttpBrowser(HttpClient::create());
        $this->entityManager = $entityManager;
    }

    /**
     * Scrapes the website for titles and stores them in the database.
     * 
     * @param string $url The URL of the website to scrape.
     * @param User $user The user who initiated the scrape.
     * @param Category|null $category The category for the resource.
     * 
     * @return array A list of scraped titles.
     */
    public function scrapeWebsite(string $url, User $user, ?Category $category): array
    {
        try {
            // Vérification si l'URL est bien définie
            if (empty($url)) {
                throw new \Exception("The URL is empty. Cannot scrape.");
            }

            // Send a GET request to the website
            $crawler = $this->client->request('GET', $url);

            // Check if the request was successful
            if ($this->client->getResponse()->getStatusCode() !== 200) {
                throw new \Exception("Failed to retrieve the website: {$url}");
            }

            // Récupérer les titres <h2>
            $titles = $crawler->filter('h2')->each(fn(Crawler $node) => $node->text());

            // Sauvegarde en base de données dans ScrapedData
            foreach ($titles as $title) {
                // Créer une nouvelle entrée ScrapedData
                $scrapedData = new ScrapedData();
                $scrapedData->setTitle($title);

                // Persister l'entrée en base de données
                $this->entityManager->persist($scrapedData);
            }

            // Enregistrer dans la base de données
            $this->entityManager->flush();

            return $titles;

        } catch (TransportExceptionInterface | DecodingExceptionInterface $e) {
            // Gestion des erreurs liées à la requête HTTP
            throw new \Exception("Error while scraping the website: " . $e->getMessage());
        } catch (\Exception $e) {
            // Gestion des autres erreurs
            throw new \Exception("An error occurred: " . $e->getMessage());
        }
    }
}
