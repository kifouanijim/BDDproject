<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Resource;
use App\Repository\ResourceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/comment')]
class CommentController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private ResourceRepository $resourceRepository;

    public function __construct(EntityManagerInterface $entityManager, ResourceRepository $resourceRepository)
    {
        $this->entityManager = $entityManager;
        $this->resourceRepository = $resourceRepository;
    }

    /**
     * Affiche le formulaire pour ajouter un commentaire.
     */
    #[Route('/{id}/new', name: 'comment_form', methods: ['GET'])]
    public function showCommentForm(Resource $resource): Response
    {
        return $this->render('comment/new.html.twig', [
            'resource' => $resource
        ]);
    }

    /**
     * Ajoute un commentaire à une ressource.
     */
    #[Route('/add', name: 'comment_add', methods: ['POST'])]
    public function addComment(Request $request): Response
    {
        $data = $request->request->all();

        if (empty($data['resource_id']) || empty($data['content'])) {
            $this->addFlash('error', 'Veuillez remplir le champ du commentaire.');
            return $this->redirectToRoute('comment_form', ['id' => $data['resource_id']]);
        }

        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour commenter.');
            return $this->redirectToRoute('app_login');
        }

        $resource = $this->entityManager->getRepository(Resource::class)->find($data['resource_id']);
        if (!$resource) {
            throw $this->createNotFoundException('Ressource introuvable.');
        }

        $comment = new Comment();
        $comment->setContent($data['content']);
        $comment->setUser($user);
        $comment->setResource($resource);
        $comment->setCreatedAt(new \DateTime());

        $this->entityManager->persist($comment);
        $this->entityManager->flush();

        $this->addFlash('success', 'Commentaire ajouté avec succès.');

        return $this->redirectToRoute('app_resource_index');
    }

    /**
     * Récupère les commentaires d'une ressource spécifique.
     */
    #[Route('/resource/{id}', name: 'comment_list', methods: ['GET'])]
    public function getComments(Resource $resource): Response
    {
        return $this->render('comment/list.html.twig', [
            'resource' => $resource,
            'comments' => $resource->getComments(),
        ]);
    }
}
