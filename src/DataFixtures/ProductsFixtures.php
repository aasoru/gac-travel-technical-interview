<?php

namespace App\DataFixtures;

use App\Entity\Categories;
use App\Entity\Products;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ProductsFixtures extends Fixture
{
    var $client;
    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    public function load(ObjectManager $manager)
    {

        $response = $this->client->request( 'GET', 'https://fakestoreapi.com/products?limit=10' );

        foreach(json_decode($response->getContent()) as $element)
        {
            $product = new Products();
            $product->setName($element->title);
            $categories = $manager->getRepository(Categories::class)->findBy(["name" =>  $element->category]);
            $product->setStock(0);
            $product->setCategory($categories[0]);
            $product->setCreatedAt(new \DateTimeImmutable('now'));
            $manager->persist($product);
            $manager->flush();
        }

    }
}