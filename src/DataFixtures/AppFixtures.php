<?php

namespace App\DataFixtures;

use App\Entity\Book;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);
        for($i=0; $i<20; $i++) {
            $book = new Book();
            $book->setTitle('Titre '.($i));
            $book->setCoverText('Quatrieme de couverture numéro : '.$i);
            $manager->persist($book);
        }
        $manager->flush();
    }
}
