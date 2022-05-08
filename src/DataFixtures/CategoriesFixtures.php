<?php

namespace App\DataFixtures;

use App\Entity\Categories;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CategoriesFixtures extends Fixture
{
    var $client;
    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    public function load(ObjectManager $manager)
    {
        $response = $this->client->request( 'GET', 'https://fakestoreapi.com/products/categories?limit=10' );

        foreach(json_decode($response->getContent()) as $element)
        {
            $category = new Categories();
            $category->setName($element);
            $category->setCreatedAt(new \DateTimeImmutable('now'));
            $manager->persist($category);
            $manager->flush();
        }

    }
}
