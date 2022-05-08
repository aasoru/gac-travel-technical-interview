<?php

namespace App\Controller;

use App\Entity\Products;
use App\Entity\StockHistoric;
use App\Form\ModifyStockType;
use App\Form\ProductsType;
use App\Repository\CategoriesRepository;
use App\Repository\ProductsRepository;
use App\Repository\StockHistoricRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/products')]
class ProductsController extends AbstractController
{
    public function __construct(Security $security)
    {
        $this->path = 'products';
        $this->security = $security;
    }

    #[Route('/', name: 'products_index', methods: ["GET"])]
    public function index(ProductsRepository $productsRepository): Response
    {
        $renderParameters = [
            'products' => $productsRepository->findAll(),
            'logged_user' => $this->security->getUser(),
            'path' => $this->path,
        ];

        return $this->render('products/index.html.twig', $renderParameters);
    }

    #[Route('/new', name: 'products_new', methods: ["GET", "POST"])]
    public function add(Request $request, ValidatorInterface $validator, CategoriesRepository $categoriesRepository): Response
    {
        $product = new Products();
        $form = $this->createForm(ProductsType::class, $product);
        $form->handleRequest($request);
        $errorMessages = [];

        $allParameters = $request->request->all();
        if(isset($allParameters['products']['name']) && isset($allParameters['products']['category'])) {

            $input = [
                'name' => $allParameters['products']['name'],
                'category' => $allParameters['products']['category'],
            ];

            $constraints = new Assert\Collection([
                'name' => [new Assert\NotBlank],
                'category' => [new Assert\NotBlank],
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
            $product->setCreatedAt(new \DateTimeImmutable('now'));
            $product->setStock(0);

            $entityManager->persist($product);
            $entityManager->flush();

            return $this->redirectToRoute('products_index', [], Response::HTTP_SEE_OTHER);
        }

        $renderParameters = [
            'product' => $product,
            'form' => $form,
            'errors' => $errorMessages,
            'logged_user' => $this->security->getUser(),
            'path' => $this->path,
        ];

        return $this->renderForm('products/new.html.twig', $renderParameters);
    }

    #[Route('/{id}/edit', name: 'products_edit', methods: ["GET", "POST"])]
    public function edit(Request $request, Products $product,ValidatorInterface $validator): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $repository = $entityManager->getRepository(Products::class);
        $product = $repository->find($product);

        $form = $this->createForm(ProductsType::class, $product);
        $form->handleRequest($request);
        $errorMessages = [];

        $allParameters = $request->request->all();
        if(isset($allParameters['products']['name']) && isset($allParameters['products']['category'])) {

            $input = [
                'name' => $allParameters['products']['name'],
                'category' => $allParameters['products']['category'],
            ];

            $constraints = new Assert\Collection([
                'name' => [new Assert\NotBlank],
                'category' => [new Assert\NotBlank],
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
            $entityManager->persist($product);
            $entityManager->flush();

            return $this->redirectToRoute('products_index', [], Response::HTTP_SEE_OTHER);
        }

        $renderParameters = [
            'product' => $product,
            'form' => $form,
            'errors' => $errorMessages,
            'logged_user' => $this->security->getUser(),
            'path' => $this->path,
        ];

        return $this->renderForm('products/edit.html.twig', $renderParameters);
    }

    #[Route('/{id}/modify-stock', name: 'products_modify_stock', methods: ["GET", "POST"])]
    public function products_modify_stock(Request $request, Products $product,ValidatorInterface $validator): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $repository = $entityManager->getRepository(Products::class);
        $product = $originalProduct = $repository->find($product);
        $stock = $product->getStock();

        $form = $this->createForm(ModifyStockType::class, $product);
        $errorMessages = [];

        $allParameters = $request->request->all();
        if(isset($allParameters['modify_stock']['stock'])) {

            $input = [ 'stock' => $allParameters['modify_stock']['stock'] ];

            $constraints = new Assert\Collection([ 'stock' => [new Assert\NotBlank] ]);

            $violations = $validator->validate($input, $constraints);
            if ( 0 !== count($violations) ) {
                $accessor = PropertyAccess::createPropertyAccessor();
                foreach ($violations as $violation) {
                    $accessor->setValue($errorMessages, $violation->getPropertyPath(), $violation->getMessage());
                }
            } else {
                $form->handleRequest($request);
            }
        }

        if ($form->isSubmitted() && $form->isValid() && count($errorMessages) === 0) {
            if( $stock + intval($product->getStock()) <= 0 )
            {
                $errorMessages['no_stock'] = "El número de stock a eliminar no debe ser mayor del stock restante";

                $renderParameters = [
                    'product' => $originalProduct,
                    'form' => $form,
                    'errors' => $errorMessages,
                    'logged_user' => $this->security->getUser(),
                    'path' => $this->path,
                ];
                return $this->renderForm('products/modify_stock.html.twig', $renderParameters);
            }

            $stockHistoric = new StockHistoric();
            $user = $this->security->getUser();
            $stockHistoric->setUser($user);
            $stockHistoric->setProduct($product);
            $stockHistoric->setCreatedAt(new \DateTimeImmutable('now'));
            $stockHistoric->setStock($product->getStock());
            $entityManager->persist($stockHistoric);

            $product->setStock($stock + $product->getStock());
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($product);
            $entityManager->flush();

            return $this->redirectToRoute('products_index', [], Response::HTTP_SEE_OTHER);
        }

        $renderParameters = [
            'product' => $product,
            'form' => $form,
            'errors' => $errorMessages,
            'logged_user' => $this->security->getUser(),
            'path' => $this->path,
        ];

        return $this->renderForm('products/modify_stock.html.twig', $renderParameters);
    }

    #[Route('/{id}/historic', name: 'products_historic', methods: ["GET"])]
    public function historic(Products $product, ProductsRepository $productsRepository, StockHistoricRepository $historicRepository): Response
    {

        $entityManager = $this->getDoctrine()->getManager();
        $repository = $entityManager->getRepository(Products::class);
        $product = $repository->find($product);

        $repository = $entityManager->getRepository(StockHistoric::class);
        $historic = $repository->findBy(["product" => $product->getId()]);
        
        $renderParameters = [
            'product' => $product,
            'historic' => $historic,
            'logged_user' => $this->security->getUser(),
            'path' => $this->path,
        ];

        return $this->render('products/historic.html.twig', $renderParameters);
    }


    #[Route('/{id}/delete', name: 'products_delete', methods: ["DELETE"])]
    public function delete($id)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $repository = $this->getDoctrine()->getRepository(Products::class);
        $products = $repository->find($id);

        $entityManager->remove($products);
        $entityManager->flush();

        return $this->redirectToRoute('products_index');
    }
}
