<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class IndexPageController extends AbstractController {
  #[Route('/', name: 'app_index_page')]
  public function index(): Response {
    return $this->render('index_page/index.html.twig', []);
  }
}
