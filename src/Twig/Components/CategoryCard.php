<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('CategoryCard')]
class CategoryCard {
  public string $name;
  public ?string $description = null;
  public ?string $imageUrl = null;
  public ?string $slug = null;
  public ?int $productCount = null;
  public ?string $href = null;
  public string $variant = 'default';
}