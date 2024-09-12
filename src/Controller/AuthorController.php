<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use App\Repository\AuthorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\Author; 
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AuthorController extends AbstractController
{
    #[Route('/api/authors', name: 'author', methods: ['GET'])]
    public function getAllAuthors(AuthorRepository $authorRepository, SerializerInterface $serialiser): JsonResponse
    {

        $authors = $authorRepository->findAll();
        $jsonAuthorList = $serialiser->serialize($authors, 'json', ['groups' => ['getAuthor']]);
        return new JsonResponse(
            $jsonAuthorList,
            Response::HTTP_OK,
            ['Content-Type' => 'application/json'],
            true
        );
    }
    #[Route('/api/authors/{id}', name: 'detailauthor', methods: ['GET'])]
    public function getDetailAuthors(int $id, AuthorRepository $authorRepository, SerializerInterface $serialiser): JsonResponse
    {

        $author = $authorRepository->find($id);
        $jsonAuthor = $serialiser->serialize($author, 'json', ['groups' => ['getAuthor']]);
        return new JsonResponse(
            $jsonAuthor,
            Response::HTTP_OK,
            ['Content-Type' => 'application/json'],
            true
        );
    }
    #[Route('/api/authors', name: 'addauthor', methods: ['POST'])]
    public function addAuthor(
                        AuthorRepository $authorRepository, 
                        EntityManagerInterface $entityManager, 
                        SerializerInterface $serialiser, 
                        Request $request, 
                        UrlGeneratorInterface $urlGenerator,
                        ): JsonResponse
    {
        $author = $serialiser->deserialize($request->getContent(), Author::class, 'json');

        $entityManager->persist($author);
        $entityManager->flush();
        
        $jsonAuthor = $serialiser->serialize($author, 'json', ['groups' =>'getAuthor']);

        // Generate a URL for the newly created book, to grab it in the header and test it straits.
        $location = $urlGenerator->generate('detailauthor', ['id' => $author->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse(
            $jsonAuthor,
            Response::HTTP_CREATED,
            ['location' => $location],
            true
        );
    }
    #[Route('/api/authors/{id}', name: 'updateauthor', methods: ['PUT'])]
    public function updateAuthor(
                SerializerInterface $serialiser, 
                Request $request, 
                int $id,
                Author $currentAuthor,
                EntityManagerInterface $entityManager,
                ): JsonResponse
    {
        $updatedAuthor = $serialiser->deserialize($request->getContent(), 
                                                Author::class, 'json', 
                                                [AbstractNormalizer::OBJECT_TO_POPULATE => $currentAuthor]
                                                );

        $entityManager->persist($updatedAuthor);
        $entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
    #[Route('/api/authors/{id}', name: 'deleteauthor', methods: ['DELETE'])]
    public function deleteAuthor(AuthorRepository $authorRepository, int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $author = $authorRepository->find($id);
        if($author){
            $books = $author->getBooks();
            foreach($books as $book) {
                $entityManager->remove($book);
            }
            $entityManager->remove($author);
            $entityManager->flush();
            return new JsonResponse(
                null,
                Response::HTTP_NO_CONTENT
            );
        }
        return new JsonResponse(
            null,
            Response::HTTP_NOT_FOUND,
        );
    }

}
