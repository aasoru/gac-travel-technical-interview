<?php

namespace App\Controller;
use App\Form\CategoryType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\Categories;
use App\Repository\CategoriesRepository;

#[Route('/categories')]
class CategoriesController extends AbstractController
{

    public function __construct(Security $security)
    {
        $this->path = 'categories';
        $this->security = $security;
    }


    #[Route('/', name: 'categories_index', methods: ["GET"])]
    public function index(CategoriesRepository $categoriesRepository): Response
    {
        $renderParameters = [
            'categories' => $categoriesRepository->findAll(),
            'logged_user' => $this->security->getUser(),
            'path' => $this->path
        ];

        return $this->render('categories/index.html.twig', $renderParameters);
    }

    #[Route('/new', name: 'categories_new', methods: ["GET", "POST"])]
    public function add(Request $request,ValidatorInterface $validator): Response
    {
        $category = new Categories();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);
        $errorMessages = [];

        $allParameters = $request->request->all();
        if(isset($allParameters['category']['name'])) {

            $input = [ 'name' => $allParameters['category']['name'] ];

            $constraints = new Assert\Collection([ 'name' => [new Assert\NotBlank] ]);

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
            $category->setCreatedAt(new \DateTimeImmutable('now'));

            $entityManager->persist($category);
            $entityManager->flush();

            return $this->redirectToRoute('categories_index', [], Response::HTTP_SEE_OTHER);
        }

        $renderParameters = [
            'category' => $category,
            'form' => $form,
            'errors' => $errorMessages,
            'logged_user' => $this->security->getUser(),
            'path' => $this->path,
        ];

        return $this->renderForm('categories/new.html.twig', $renderParameters);
    }


    #[Route('/{id}', name: 'categories_show', methods: ["GET", "POST"])]
    public function show(Categories $category): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $repository = $entityManager->getRepository(Categories::class);
        $form = $this->createForm(CategoryType::class, $category);
        $category = $repository->find($category);

        $renderParameters = [
            'category' => $category,
            'form' => $form,
            'errors' => [],
            'logged_user' => $this->security->getUser(),
            'path' => $this->path,
        ];

        return $this->renderForm('categories/show.html.twig', $renderParameters);
    }


    #[Route('/{id}/edit', name: 'categories_edit', methods: ["GET", "POST"])]
    public function edit(Request $request, Categories $category,ValidatorInterface $validator): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $repository = $entityManager->getRepository(Categories::class);
        $category = $repository->find($category);
        $form = $this->createForm(CategoryType::class, $category);
        $errorMessages = [];

        $allParameters = $request->request->all();
        if(isset($allParameters['category']['name'])) {

            $input = [ 'name' => $allParameters['category']['name'] ];
            $constraints = new Assert\Collection([ 'name' => [new Assert\NotBlank] ]);

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
            $entityManager->flush();

            return $this->redirectToRoute('categories_index', [], Response::HTTP_SEE_OTHER);
        }

        $renderParameters = [
            'category' => $category,
            'form' => $form,
            'errors' => $errorMessages,
            'logged_user' => $this->security->getUser(),
            'path' => $this->path,
        ];

        return $this->renderForm('categories/edit.html.twig', $renderParameters);
    }


    #[Route('/{id}/delete', name: 'categories_delete', methods: ["DELETE"])]
    public function delete($id)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $repository = $this->getDoctrine()->getRepository(Categories::class);
        $category = $repository->find($id);

        $entityManager->remove($category);
        $entityManager->flush();

        return $this->redirectToRoute('categories_index');
    }
}
