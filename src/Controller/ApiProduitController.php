<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Produit;

#[Route('/api/produit', name: 'api_produit')]
final class ApiProduitController extends AbstractController
{
    #[Route('/', name: 'liste', methods: ['GET'])]
    public function liste(EntityManagerInterface $entityManager): JsonResponse
    {
        $produits = $entityManager->getRepository(Produit::class)->findAll();
        return $this->json($produits, 200, []);
    }
}
