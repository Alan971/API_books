<?php

namespace App\Controller;

use App\Entity\Book;
use App\Repository\AuthorRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\BookRepository;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class BookController extends AbstractController

{
    #[Route('/api/books', name: 'book', methods: ['GET'])]
    public function getAllBooks(BookRepository $bookRepository, SerializerInterface $serialiser): JsonResponse
    {
        $bookList = $bookRepository->findAll();
        $jsonBookList = $serialiser->serialize($bookList, 'json', ['groups' =>'getBooks']);

        return new JsonResponse(
            $jsonBookList,
            Response::HTTP_OK,
            ['Content-Type' => 'application/json'],
            true
        );
    }
    #[Route('/api/books/{id}', name: 'detailbook', methods: ['GET'])]
    public function getDetailBooks(BookRepository $bookRepository, SerializerInterface $serialiser, int $id): JsonResponse
    {
        $book = $bookRepository->find($id);
        if($book){
            $jsonBook = $serialiser->serialize($book, 'json', ['groups'=>'getBooks']);
            return new JsonResponse(
                $jsonBook,
                Response::HTTP_OK,
                ['Content-Type' => 'application/json'],
                true
            );
        }
        return new JsonResponse(
            null,
            Response::HTTP_NOT_FOUND,
        );
    }
    #[Route('/api/books/{id}', name: 'deletebook', methods: ['DELETE'])]
    public function deleteBook(BookRepository $bookRepository, int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $book = $bookRepository->find($id);
        if($book){
            $entityManager->remove($book);
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
    #[Route('/api/books', name: 'createbook', methods: ['POST'])]
    public function createBook(
        SerializerInterface $serialiser, 
        Request $request, 
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $urlGenerator,
        AuthorRepository $authorRepository
        ): JsonResponse
    {
        $book = $serialiser->deserialize($request->getContent(), Book::class, 'json');
        $arrayBook = $request->toArray();
        $idAuthor = $arrayBook['idAuthor'] ?? -1;
        $book->setAuthor($authorRepository->find($idAuthor));
  
        $entityManager->persist($book);
        $entityManager->flush();
        //même pas besoin de faire un find() pour récupérer le book
        $jsonBook = $serialiser->serialize($book, 'json', ['groups' =>'getBooks']);

        // Generate a URL for the newly created book, to grab it in the header and test it straits.
        $location = $urlGenerator->generate('detailbook', ['id' => $book->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse(
            $jsonBook,
            Response::HTTP_CREATED,
            ['location' => $location],
            true
        );
    }
    #[Route('/api/books/{id}', name: 'updatebook', methods: ['PUT'])]
    public function updateBook(
        SerializerInterface $serialiser, 
        Request $request, 
        EntityManagerInterface $entityManager,
        Book $currentBook,
        AuthorRepository $authorRepository
        ): JsonResponse
    {
        $updatedBook = $serialiser->deserialize($request->getContent(), 
                                                Book::class, 'json', 
                                                [AbstractNormalizer::OBJECT_TO_POPULATE => $currentBook]
                                                );
        $arrayBook = $request->toArray();
        $idAuthor = $arrayBook['idAuthor'] ?? -1;
        $updatedBook->setAuthor($authorRepository->find($idAuthor));
  
        $entityManager->persist($updatedBook);
        $entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
