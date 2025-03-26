<?php

namespace App\Controller;

use App\Entity\Resource;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/resources', name: 'api_resources_')]
class ApiResourceController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(EntityManagerInterface $entityManager, SerializerInterface $serializer): JsonResponse
    {
        $resources = $entityManager->getRepository(Resource::class)->findAll();
        $json = $serializer->serialize($resources, 'json', ['groups' => 'resource:read']);
        return new JsonResponse($json, 200, [], true);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id, EntityManagerInterface $entityManager, SerializerInterface $serializer): JsonResponse
    {
        $resource = $entityManager->getRepository(Resource::class)->find($id);
        if (!$resource) {
            return new JsonResponse(['error' => 'Resource not found'], 404);
        }
        $json = $serializer->serialize($resource, 'json', ['groups' => 'resource:read']);
        return new JsonResponse($json, 200, [], true);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $resource = new Resource();

        $resource->setTitle($data['title'] ?? '');
        $resource->setDescription($data['description'] ?? null);
        $resource->setUrl($data['url'] ?? '');
        $resource->setCreatedAt(new \DateTime());

        $errors = $validator->validate($resource);
        if (count($errors) > 0) {
            return new JsonResponse(['error' => (string) $errors], 400);
        }

        $entityManager->persist($resource);
        $entityManager->flush();

        $json = $serializer->serialize($resource, 'json', ['groups' => 'resource:read']);
        return new JsonResponse($json, 201, [], true);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $resource = $entityManager->getRepository(Resource::class)->find($id);
        if (!$resource) {
            return new JsonResponse(['error' => 'Resource not found'], 404);
        }
        $entityManager->remove($resource);
        $entityManager->flush();
        return new JsonResponse(['message' => 'Resource deleted'], 200);
    }
}
