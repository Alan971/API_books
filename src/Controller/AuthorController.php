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
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AuthorController extends AbstractController
{
    /**
     * Cette méthode permet de récupérer l'ensemble des auteurs. 
     *
     * @param AuthorRepository $authorRepository
     * @param SerializerInterface $serializer
     * @return JsonResponse
     */
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
    /**
     * Cette méthode permet de récupérer un auteur en particulier en fonction de son id. 
     *
     * @param Author $author
     * @param SerializerInterface $serializer
     * @return JsonResponse
     */
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

    /**
     * Cette méthode permet de créer un nouvel auteur. Elle ne permet pas 
     * d'associer directement des livres à cet auteur. 
     * Exemple de données :
     * {
     *     "lastName": "Tolkien",
     *     "firstName": "J.R.R"
     * }
     *
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $entityManager
     * @param UrlGeneratorInterface $urlGenerator
     * @param ValidatorInterface $validator
     * @return JsonResponse
     */
    #[Route('/api/authors', name: 'addauthor', methods: ['POST'])]
    public function addAuthor(
                        AuthorRepository $authorRepository, 
                        EntityManagerInterface $entityManager, 
                        SerializerInterface $serialiser, 
                        Request $request, 
                        UrlGeneratorInterface $urlGenerator,
                        ValidatorInterface $validator
                        ): JsonResponse
    {
        $author = $serialiser->deserialize($request->getContent(), Author::class, 'json');

        $error = $validator->validate($author);
        if($error->count() > 0) {
            return new JsonResponse($serialiser->serialize($error, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

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
    /**
     * Cette méthode permet de mettre à jour un auteur. 
     * Exemple de données :
     * {
     *     "lastName": "Tolkien",
     *     "firstName": "J.R.R"
     * }
     * 
     * Cette méthode ne permet pas d'associer des livres et des auteurs.
     * 
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param Author $currentAuthor
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse
     */
    #[Route('/api/authors/{id}', name: 'updateauthor', methods: ['PUT'])]
    public function updateAuthor(
                SerializerInterface $serialiser, 
                Request $request, 
                int $id,
                Author $currentAuthor,
                EntityManagerInterface $entityManager,
                ValidatorInterface $validator
                ): JsonResponse
    {
        $error = $validator->validate($currentAuthor);
        if($error->count() > 0) {
            return new JsonResponse($serialiser->serialize($error, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }
        $updatedAuthor = $serialiser->deserialize($request->getContent(), 
                                                Author::class, 'json', 
                                                [AbstractNormalizer::OBJECT_TO_POPULATE => $currentAuthor]
                                                );

        $entityManager->persist($updatedAuthor);
        $entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
     /**
     * Cette méthode supprime un auteur en fonction de son id. 
     * En cascade, les livres associés aux auteurs seront aux aussi supprimés. 
     *
     * /!\ Attention /!\
     * pour éviter le problème :
     * "1451 Cannot delete or update a parent row: a foreign key constraint fails"
     * Il faut bien penser rajouter dans l'entité Book, au niveau de l'author :
     * #[ORM\JoinColumn(onDelete:"CASCADE")]
     * 
     * Et resynchronizer la base de données pour appliquer ces modifications. 
     * avec : php bin/console doctrine:schema:update --force
     * 
     * @param Author $author
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
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
