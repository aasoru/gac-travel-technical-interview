<?php

namespace App\Controller;

use App\Entity\Users;
use App\Form\SignUpType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class SignUpController extends AbstractController
{

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
        $this->path = 'sign-up';
    }

    #[Route('/sign-up', name: 'sign_up')]
    public function index(Request $request,ValidatorInterface $validator): Response
    {
        $user = new Users();
        $form = $this->createForm(SignUpType::class, $user);
        $form->handleRequest($request);
        $errorMessages = [];

        $allParameters = $request->request->all();
        if(isset($allParameters['sign_up']['username']) && isset($allParameters['sign_up']['password'])) {

            $input = [
                'username' => $allParameters['sign_up']['username'],
                'password' => $allParameters['sign_up']['password'],
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

            if ($form->isSubmitted() && $form->isValid() && count($errorMessages) === 0) {
                $entityManager = $this->getDoctrine()->getManager();

                //comprobar si el  usuario ya existe
                $repository = $entityManager->getRepository(Users::class);
                $users = $repository->findBy(array("username" => $input['username']));
                if(count($users) > 0)
                    return $this->redirectToRoute('sign_up_error', [], Response::HTTP_SEE_OTHER);

                $user->setPassword($this->passwordHasher->hashPassword($user, $input['password']));
                $user->setActive(1);
                $user->setCreatedAt(new \DateTimeImmutable('now'));
                $user->setRoles(array("ROLE_ADMIN"));

                $entityManager->persist($user);
                $entityManager->flush();

                return $this->redirectToRoute('sign_up_success', [], Response::HTTP_SEE_OTHER);
            }
        }

        $renderParameters = [
            'user' => $user,
            'form' => $form,
            'errors' => $errorMessages,
            'path' => $this->path,
        ];

        return $this->renderForm('auth/sign_up/index.html.twig', $renderParameters);
    }


    #[Route('/sign-up/success', name: 'sign_up_success')]
    public function success() : Response
    {
        $renderParameters = ['path' => $this->path];
        return $this->render('auth/sign_up/success.html.twig', $renderParameters);
    }


    #[Route('/sign-up/error', name: 'sign_up_error')]
    public function error() : Response
    {
        $renderParameters = ['path' => $this->path];
        return $this->render('auth/sign_up/error.html.twig', $renderParameters);
    }
}
