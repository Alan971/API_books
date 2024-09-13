<?php

namespace App\DataFixtures;

use App\Entity\Book;
use App\Entity\Author;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $userPasswordHasher;
    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHasher = $userPasswordHasher;
    }
    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail('user@bookapi.com');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($this->userPasswordHasher->hashPassword(
            $user,
            'test'
        ));
        $manager->persist($user);

        $userAdmin = new User();
        $userAdmin->setEmail('admin@bookapi.com');
        $userAdmin->setRoles(['ROLE_ADMIN']);
        $userAdmin->setPassword($this->userPasswordHasher->hashPassword(
            $userAdmin,
            'test'
        ));
        $manager->persist($userAdmin);

        for($i = 0; $i < 10; $i++) {
            $author = new Author();
            $author->setFirstName('FirstName '.$i);
            $author->setLastName('LastName '.$i);
            $manager->persist($author);

            $listAuthor[] = $author;
        }
        // $product = new Product();
        // $manager->persist($product);
        for($i=0; $i<20; $i++) {
            $book = new Book();
            $book->setTitle('Titre '.($i));
            $book->setCoverText('Quatrieme de couverture numéro : '.$i);
            $book->setAuthor($listAuthor[rand(0, 9)]);
            $manager->persist($book);

        }
        $manager->flush();
    }
}
