<?php

namespace App\Controller; 

use App\Entity\Produit;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Form\ProductType;
use Symfony\Component\HttpFoundation\Request;

class ProduitController extends AbstractController
{
	
	#[Route('/produit/ajouter', name: 'ajouter_produit')]
	public function ajouter(Request $request, EntityManagerInterface $entityManager): Response
	{
		$produit = new Produit();
		$form = $this->createForm(ProductType::class, $produit);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$entityManager->persist($produit);
			$entityManager->flush();
			return $this->redirectToRoute('produit_liste');
		}

		return $this->render('produit/add.html.twig', [
			'form' => $form->createView(),
		]);
	}

	#[Route('/produit', name: 'produit_liste')]
	public function index(): Response
	{
		$produits = $entityManager->getRepository(Produit::class)->finAll();
		return $this->render('produit/list.html.twig', [
			'produits' => $produits,
		
	}
}
