<?php

namespace App\Controller;

use App\Entity\Resource;
use App\Entity\User;
use App\Entity\Category;
use App\Form\Resource1Type;
use App\Service\ScraperService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/resource')]
final class ResourceController extends AbstractController
{
    private ScraperService $scraperService;

    public function __construct(ScraperService $scraperService)
    {
        $this->scraperService = $scraperService;
    }

    #[Route(name: 'app_resource_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $resources = $entityManager->getRepository(Resource::class)->findAll();

        return $this->render('resource/index.html.twig', [
            'resources' => $resources,
        ]);
    }

    #[Route('/new', name: 'app_resource_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser(); // Récupérer l'utilisateur connecté

        if (!$user) {
            return $this->redirectToRoute('app_login'); // Rediriger vers la connexion si non authentifié
        }

        $resource = new Resource();
        $form = $this->createForm(Resource1Type::class, $resource);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $resource->setUser($user); // Associer l'utilisateur connecté à la ressource
            $entityManager->persist($resource);
            $entityManager->flush();

            return $this->redirectToRoute('app_resource_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('resource/new.html.twig', [
            'resource' => $resource,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_resource_show', methods: ['GET'])]
    public function show(Resource $resource): Response
    {
        return $this->render('resource/show.html.twig', [
            'resource' => $resource,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_resource_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Resource $resource, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(Resource1Type::class, $resource);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_resource_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('resource/edit.html.twig', [
            'resource' => $resource,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_resource_delete', methods: ['POST'])]
    public function delete(Request $request, Resource $resource, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $resource->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($resource);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_resource_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/scrape', name: 'app_resource_scrape', methods: ['POST'])]
    public function scrape(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'User is not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $url = $request->get('url');

        if (!$url) {
            return $this->json(['error' => 'URL is required'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $categoryId = $request->get('categoryId');
            $category = $categoryId ? $entityManager->getRepository(Category::class)->find($categoryId) : null;

            // Scraping du site et création de la ressource
            $titles = $this->scraperService->scrapeWebsite($url, $user, $category);

            // Création et enregistrement de la nouvelle ressource
            $resource = new Resource();
            $resource->setUrl($url);
            $resource->setUser($user);
            $resource->setCategory($category);
            $resource->setCreatedAt(new \DateTime());

            $entityManager->persist($resource);
            $entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Resource successfully scraped and saved.',
                'titles' => $titles
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
