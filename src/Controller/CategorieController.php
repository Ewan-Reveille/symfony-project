<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Categorie;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\CategorieFormType;
final class CategorieController extends AbstractController
{
    #[Route('/categorie', name: 'categorie_liste')]
    public function index(): Response
    {
        return $this->render('categorie/index.html.twig', [
            'controller_name' => 'CategorieController',
        ]);
    }

    #[Route('/categorie/ajouter', name: 'ajouter_categorie')]
	public function ajouter(Request $request, EntityManagerInterface $entityManager): Response
	{
		$categorie = new Categorie();
		$form = $this->createForm(CategorieFormType::class, $categorie);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$entityManager->persist($categorie);
			$entityManager->flush();
			return $this->redirectToRoute('categorie_liste');
		}

		return $this->render('categorie/add.html.twig', [
			'form' => $form->createView(),
		]);
	}
}
