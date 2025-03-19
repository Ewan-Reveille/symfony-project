<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AboutController extends AbstractController
{
    #[Route('/about-us', name: 'app_about_us')]
    public function index(): Response
    {
        $firstName = "ewan";
        $lastName = "réveillé";
        $birthDate = "23/08/2004";
        $address = "3 rue du Val-Vert";
        $description = "
            

Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer id metus eu velit volutpat tempor vitae scelerisque nisl. Integer rhoncus auctor blandit. Nullam consequat ex nec bibendum malesuada. Vivamus ac nisi ac massa porttitor interdum. Sed bibendum efficitur lectus at lacinia. Suspendisse vel gravida elit. Cras facilisis turpis quis nisi consequat, eget pharetra massa consequat. Vestibulum luctus interdum facilisis. Praesent euismod gravida luctus. Praesent suscipit pretium lacus eget commodo. Aenean hendrerit tincidunt justo vitae scelerisque. Interdum et malesuada fames ac ante ipsum primis in faucibus. Cras aliquet imperdiet sapien eu laoreet.

Cras id leo mi. Sed massa justo, aliquet in aliquet at, vestibulum et dui. Aliquam nec dignissim tortor, et sollicitudin nulla. Duis varius orci felis, id faucibus est venenatis in. In aliquet sem nec dictum facilisis. Donec non facilisis felis. Nullam pharetra urna nibh, eu laoreet leo fringilla vel. Mauris et augue sit amet quam elementum consequat eget eu nisi.

Mauris interdum mauris lacus, id vestibulum mi dignissim suscipit. Nunc mattis ipsum ipsum, a lacinia diam tristique a. Suspendisse id ultricies ligula. Etiam varius nisi felis, sit amet cursus augue porttitor ac. Donec lacinia congue tortor id pharetra. Fusce sollicitudin congue ante, id pellentesque tellus luctus vitae. Donec magna dolor, pharetra tincidunt massa eget, sodales fermentum magna. Aenean eu nisl in mauris dignissim ullamcorper. Cras sed magna massa. Integer non congue augue, non tempus dui. Ut sit amet diam id dolor egestas euismod. Fusce at sapien tempus, posuere urna nec, suscipit diam.

Sed sagittis efficitur felis vitae accumsan. Aenean porttitor eu elit sed venenatis. Donec congue facilisis ipsum in tempus. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vivamus lectus nibh, venenatis vel metus eu, vehicula iaculis nulla. In imperdiet consequat tortor quis commodo. Integer feugiat tellus sed ex fringilla vehicula. Etiam rhoncus sit amet risus sed pretium. Nam convallis aliquam ante, et tempor enim. Duis scelerisque imperdiet massa, eu fringilla tellus viverra in. Cras blandit purus nec hendrerit commodo.

Vivamus placerat tempus mauris, sed congue metus sagittis sed. Integer sollicitudin, nibh eu molestie mattis, purus purus pharetra sem, id lobortis sem purus ac turpis. Etiam suscipit pretium mi, nec lobortis massa accumsan nec. Integer fermentum augue fermentum lacus dignissim, pellentesque porta mauris mollis. Etiam consectetur orci et elementum ornare. Aliquam nulla dolor, facilisis vel dictum at, rutrum eu eros. Donec eu ligula eget felis auctor lobortis in a magna. Etiam ac commodo justo, eu placerat augue. Nulla sit amet est in elit tincidunt consectetur at at mi. Ut semper magna dui, quis sodales lacus interdum quis. Pellentesque vitae lectus imperdiet, vestibulum lectus ac, feugiat nulla. Vivamus tempor placerat risus a euismod. Aenean ex quam, posuere sed turpis at, commodo tincidunt nisi. Phasellus mattis lacus orci, eget maximus mauris pretium quis. Donec imperdiet fermentum tincidunt. Vivamus ultricies tempus erat, eget varius tortor ultrices dapibus. ";

        $iterations = [
            "AIA HANDICAP" => "Développement d'une API d'accessibilité",
            "aiashop" => "Création d'un CMS e-commerce accessible",
            "aia-tagging" => "Développement d'un moteur de sous-titrage IA"
        ];

        $favoriteMovies = [
            ["title" => "Interstellar", "year" => 2014, "watched" => true],
            ["title" => "La vie est belle", "year" => 1995, "watched" => false],
            ["title" => "Forrest Gump", "year" => 1990, "watched" => true]
        ];

        return $this->render('about/index.html.twig', [
            'firstName' => $firstName,
            'lastName' => $lastName,
            'description' => $description,
            'birthDate' => $birthDate,
            'address' => $address,
            'iterations' => $iterations,
            'movies' => $favoriteMovies,
        ]);
    }
}

