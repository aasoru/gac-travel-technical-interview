<?php

namespace App\Controller;

use App\Entity\Users;
use App\Form\UsersType;
use App\Repository\UsersRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/users')]
class UsersController extends AbstractController
{

    private UserPasswordHasherInterface $passwordHasher;
    public function __construct(UserPasswordHasherInterface $passwordHasher, Security $security)
    {
        $this->passwordHasher = $passwordHasher;
        $this->path = 'users';
        $this->security = $security;
    }


    #[Route('/', name: 'users_index', methods: ["GET"])]
    public function index(UsersRepository $usersRepository): Response
    {
        $renderParameters = [
            'users' => $usersRepository->findAll(),
            'path' => $this->path,
            'logged_user' => $this->security->getUser(),
        ];

        return $this->render('users/index.html.twig', $renderParameters);
    }


    #[Route('/new', name: 'users_new', methods: ["GET", "POST"])]
    public function new(Request $request,ValidatorInterface $validator): Response
    {
        $user = new Users();
        $form = $this->createForm(UsersType::class, $user);
        $form->handleRequest($request);
        $errorMessages = [];

        $allParameters = $request->request->all();
        if(isset($allParameters['users']['username']) && isset($allParameters['users']['password'])) {

            $input = [
                'username' => $allParameters['users']['username'],
                'password' => $allParameters['users']['password'],
                ];

            $constraints = new Assert\Collection([
                'username' => [new Assert\NotBlank],
                'password' => [new Assert\notBlank],
            ]);

            $violations = $validator->validate($input, $constraints);
            if ( 0 !== count($violations) ) {
                $accessor = PropertyAccess::createPropertyAccessor();
                foreach ($violations as $violation) {
                    $accessor->setValue($errorMessages, $violation->getPropertyPath(), $violation->getMessage());
                }
            }
        }

        if ($form->isSubmitted() && $form->isValid() && count($errorMessages) === 0) {
            $entityManager = $this->getDoctrine()->getManager();
            $user->setCreatedAt(new \DateTimeImmutable('now'));
            $user->setRoles(array("ROLE_ADMIN"));
            $user->setPassword($this->passwordHasher->hashPassword($user, $input['password']));

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('users_index', [], Response::HTTP_SEE_OTHER);
        }

        $renderParameters = [
            'user' => $user,
            'form' => $form,
            'errors' => $errorMessages,
            'path' => $this->path,
            'logged_user' => $this->security->getUser(),
        ];

        return $this->renderForm('users/new.html.twig', $renderParameters);
    }


    #[Route('/{id}', name: 'users_show', methods: ["GET"])]
    public function show(Users $user): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $repository = $entityManager->getRepository(Users::class);
        $form = $this->createForm(UsersType::class, $user);
        $usuario = $repository->find($user);

        $renderParameters = [
            'user' => $usuario,
            'path' => $this->path,
            'form' => $form,
            'errors' => [],
            'logged_user' => $this->security->getUser(),
        ];
        return $this->renderForm('users/show.html.twig', $renderParameters);
    }


    #[Route('/{id}/edit', name: 'users_edit', methods: ["GET","POST"])]
    public function edit(Request $request, Users $user,ValidatorInterface $validator): Response
    {
        $allParameters = $request->request->all();
        $entityManager = $this->getDoctrine()->getManager();
        $repository = $entityManager->getRepository(Users::class);
        $user = $repository->find($user);
        $user->setPassword("");
        $form = $this->createForm(UsersType::class, $user);
        $errorMessages = [];
        
        if(isset($allParameters['users']['username']) && isset($allParameters['users']['password'])) {
            $input = [
                'username' => $allParameters['users']['username'],
                'password' => $this->passwordHasher->hashPassword($user, $allParameters['users']['password']),
            ];

            $constraints = new Assert\Collection([
                'username' => [new Assert\NotBlank],
                'password' => [new Assert\notBlank],
            ]);

            $violations = $validator->validate($input, $constraints);

            if ( 0 !== count($violations) ) {
                $accessor = PropertyAccess::createPropertyAccessor();
                foreach ($violations as $violation) {
                    $accessor->setValue($errorMessages, $violation->getPropertyPath(), $violation->getMessage());
                }
            } else
                $form->handleRequest($request);
        }

        if ($form->isSubmitted() && $form->isValid() && count($errorMessages) === 0) {
            $entityManager = $this->getDoctrine()->getManager();
            $user->setPassword($this->passwordHasher->hashPassword($user, $input['password']));
            $entityManager->flush();

            return $this->redirectToRoute('users_index', [], Response::HTTP_SEE_OTHER);
        }

        $renderParameters = [
            'user' => $user,
            'form' => $form,
            'path' => $this->path,
            'errors' => $errorMessages,
            'logged_user' => $this->security->getUser(),
        ];

        return $this->renderForm('users/edit.html.twig', $renderParameters);
    }

    #[Route('/{id}/delete', name: 'users_delete', methods: ["DELETE"])]
    public function delete($id)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $repository = $this->getDoctrine()->getRepository(Users::class);
        $users = $repository->find($id);

        $entityManager->remove($users);
        $entityManager->flush();

        return $this->redirectToRoute('users_index');
    }
}
