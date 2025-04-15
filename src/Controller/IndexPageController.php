<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class IndexPageController extends AbstractController {
  #[Route('/index/{id}', name: 'app_index_page')]
  public function index(string $id): Response {
    return $this->render('index_page/index.html.twig', [
      'controller_name' => 'IndexPageController',
      'id' => $id
    ]);
  }
}
