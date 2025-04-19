<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Input')]
class Input {
  public string $id;
  public string $name;
  public string $type = 'text';
  public ?string $placeholder = null;
  public ?string $value = null;
  public ?string $autocomplete = null;
  public bool $required = false;
  public bool $disabled = false;
  public array $extraAttributes = [];
  public ?string $extraClasses = null;
}