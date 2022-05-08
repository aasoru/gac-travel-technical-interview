<?php

namespace App\DataFixtures;

use App\Entity\Users;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class UsersFixtures extends Fixture
{
    var $client;
    private UserPasswordHasherInterface $passwordHasher;
    public function __construct(HttpClientInterface $client, UserPasswordHasherInterface $passwordHasher)
    {
        $this->client = $client;
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager)
    {
        $response = $this->client->request( 'GET', 'https://fakestoreapi.com/users?limit=10' );

        foreach(json_decode($response->getContent()) as $element)
        {
            $user = new Users();
            $user->setUsername($element->username);
            $user->setPassword($this->passwordHasher->hashPassword($user, $element->password));
            $user->setRoles(["ROLE_ADMIN"]);   // By default all imported users are ADMINS
            $user->setActive(1);                    // By default all imported users are ACTIVATED
            $user->setCreatedAt(new \DateTimeImmutable('now'));
            $manager->persist($user);
            $manager->flush();
        }

    }
}
