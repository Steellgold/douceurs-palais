<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('BakeryCard')]
class BakeryCard {
  public string $name;
  public ?string $description = null;
  public ?string $imageUrl = null;
  public ?string $slug = null;
  public ?int $productCount = null;
  public ?string $href = null;
  public string $variant = 'default';
  public ?string $location = null;
  public ?string $address = null;
  public ?float $note = null;
  public ?string $bakeryId = null;
  public ?bool $isFavorite = false;
}