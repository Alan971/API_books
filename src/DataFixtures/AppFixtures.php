<?php

namespace App\DataFixtures;

use App\Entity\Book;
use App\Entity\Author;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
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
