<?php

namespace App\Controller;

use App\Entity\Thread;
use App\Entity\Post;
use App\Form\ThreadType;
use App\Form\PostType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ForumController extends AbstractController
{
    #[Route('/forum', name: 'forum_index')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        // Récupérer tous les threads
        $threads = $entityManager->getRepository(Thread::class)->findAll();

        return $this->render('forum/index.html.twig', [
            'threads' => $threads,
        ]);
    }

    #[Route('/forum/thread/{id}', name: 'forum_thread')]
    public function thread(Thread $thread, Request $request, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si l'utilisateur est connecté avant de permettre la création de posts
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $post = new Post();
        $post->setThread($thread);
        $post->setAuthor($this->getUser());
        $post->setCreatedAt(new \DateTimeImmutable());  // Création du Post avec la date actuelle

        // Création du formulaire pour ajouter un post
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Enregistrer le post en base de données
            $entityManager->persist($post);
            $entityManager->flush();

            return $this->redirectToRoute('forum_thread', ['id' => $thread->getId()]);
        }

        return $this->render('forum/thread.html.twig', [
            'thread' => $thread,
            'posts' => $thread->getPosts(),
            'form' => $form->createView(),
        ]);
    }

    #[Route('/forum/new', name: 'forum_new_thread')]
    public function newThread(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si l'utilisateur est connecté avant de permettre la création de threads
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $thread = new Thread();
        $thread->setAuthor($this->getUser());

        // Création du formulaire pour un nouveau thread
        $form = $this->createForm(ThreadType::class, $thread);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Enregistrer le thread en base de données
            $entityManager->persist($thread);
            $entityManager->flush();

            return $this->redirectToRoute('forum_index');
        }

        return $this->render('forum/new_thread.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
