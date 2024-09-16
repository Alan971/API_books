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
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class BookController extends AbstractController

{
    /**
     * Cette méthode permet de récupérer l'ensemble des livres. 
     *
     * @param BookRepository $bookRepository
     * @param SerializerInterface $serializer
     * @return JsonResponse
     */
    #[Route('/api/books', name: 'book', methods: ['GET'])]
    public function getAllBooks(BookRepository $bookRepository, SerializerInterface $serialiser, 
                                request $request, TagAwareCacheInterface $cachePool): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);

        $idCache = "getAllBooks-" . $page . "-" . $limit;
        $jsonBookList = $cachePool->get($idCache, function(ItemInterface $item) use ($bookRepository, $page, $limit, $serialiser)
            {
                $item->tag('booksCache');
                $bookList = $bookRepository->findAllWithPagination($page, $limit);
                return $serialiser->serialize($bookList, 'json', ['groups' =>'getBooks']);
            });

        return new JsonResponse(
            $jsonBookList,
            Response::HTTP_OK,
            ['Content-Type' => 'application/json'],
            true
        );
    }
    /**
     * Cette méthode permet de récupérer un livre en particulier en fonction de son id. 
     *
     * @param Book $book
     * @param SerializerInterface $serializer
     * @return JsonResponse
     */
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
    /**
     * Cette méthode permet de supprimer un livre par rapport à son id. 
     *
     * @param Book $book
     * @param EntityManagerInterface $em
     * @return JsonResponse 
     */
    #[Route('/api/books/{id}', name: 'deletebook', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer un livre')]
    public function deleteBook(BookRepository $bookRepository, int $id, EntityManagerInterface $entityManager, TagAwareCacheInterface $cachePool): JsonResponse
    {
        $book = $bookRepository->find($id);
        if($book){
            $entityManager->remove($book);
            $entityManager->flush();
            $cachePool->invalidateTags(['booksCache']);
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
    /**
     * Cette méthode permet d'insérer un nouveau livre. 
     * Exemple de données : 
     * {
     *     "title": "Le Seigneur des Anneaux",
     *     "coverText": "C'est l'histoire d'un anneau unique", 
     *     "idAuthor": 5
     * }
     * 
     * Le paramètre idAuthor est géré "à la main", pour créer l'association
     * entre un livre et un auteur. 
     * S'il ne correspond pas à un auteur valide, alors le livre sera considéré comme sans auteur. 
     *
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $em
     * @param UrlGeneratorInterface $urlGenerator
     * @param AuthorRepository $authorRepository
     * @return JsonResponse
     */
    #[Route('/api/books', name: 'createbook', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer un livre')]
    public function createBook(
        SerializerInterface $serialiser, 
        Request $request, 
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $urlGenerator,
        AuthorRepository $authorRepository,
        ValidatorInterface $validator, 
        TagAwareCacheInterface $cachePool
        ): JsonResponse
    {
        
        $book = $serialiser->deserialize($request->getContent(), Book::class, 'json');

        //on vérifie les erreurs
        $errors = $validator->validate($book);
        if($errors->count() > 0) {
            return new JsonResponse($serialiser->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        $arrayBook = $request->toArray();
        $idAuthor = $arrayBook['idAuthor'] ?? -1;
        $book->setAuthor($authorRepository->find($idAuthor));
  
        $entityManager->persist($book);
        $entityManager->flush();
        //on vide le cache
        $cachePool->invalidateTags(['booksCache']);

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
    /**
     * Cette méthode permet de mettre à jour un livre en fonction de son id. 
     * 
     * Exemple de données : 
     * {
     *     "title": "Le Seigneur des Anneaux",
     *     "coverText": "C'est l'histoire d'un anneau unique", 
     *     "idAuthor": 5
     * }
     *
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param Book $currentBook
     * @param EntityManagerInterface $em
     * @param AuthorRepository $authorRepository
     * @return JsonResponse
     */
    #[Route('/api/books/{id}', name: 'updatebook', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer un livre')]
    public function updateBook(
        SerializerInterface $serialiser, 
        Request $request, 
        EntityManagerInterface $entityManager,
        Book $currentBook,
        AuthorRepository $authorRepository,
        ValidatorInterface $validator,
        TagAwareCacheInterface $cachePool
        ): JsonResponse
    {
        //on vérifie les erreurs
        $errors = $validator->validate($currentBook);
        if($errors->count() > 0) {
            return new JsonResponse($serialiser->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }
        $updatedBook = $serialiser->deserialize($request->getContent(), 
                                                Book::class, 'json', 
                                                [AbstractNormalizer::OBJECT_TO_POPULATE => $currentBook]
                                                );
        $arrayBook = $request->toArray();
        $idAuthor = $arrayBook['idAuthor'] ?? -1;
        $updatedBook->setAuthor($authorRepository->find($idAuthor));
  
        $entityManager->persist($updatedBook);
        $entityManager->flush();
        $cachePool->invalidateTags(['booksCache']);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
