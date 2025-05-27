<?php

namespace App\Twig\Components;

use App\Entity\Bakery;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Navbar')]
class Navbar
{
  public function __construct(
    public ?Bakery $bakery = null,
    public bool $isBakeryPage = false,
    public string $class = ''
  ) {
  }
}