<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('ProductCard')]
class ProductCard {
  public string $name;
  public ?string $description = null;
  public ?string $imageUrl = null;
  public ?string $category = null;
  public ?float $price = null;
  public ?string $productId = null;
  public ?string $bakeryId = null;
  public string $variant = 'default';
}