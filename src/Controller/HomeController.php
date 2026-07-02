<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route(path: '/', name: 'app_homepage')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig');
    }

    #[Route(path: '/widget-demo', name: 'app_widget_demo')]
    public function widgetDemo(): Response
    {
        return $this->render('home/widget_demo.html.twig');
    }

    #[Route(path: '/recipes/tag/{tag}', name: 'app_recipes_by_tag')]
    public function recipesByTag(string $tag): Response
    {
        return $this->render('home/recipes_by_tag.html.twig', [
            'tag' => $tag,
        ]);
    }
}
