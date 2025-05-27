<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('SearchInput')]
class SearchInput {
  public ?string $placeholder = 'Entrez votre code postal ou ville...';
  public ?string $class = null;
  public ?string $id = 'location-search';
  public ?string $name = 'location';
  public ?string $value = null;
}