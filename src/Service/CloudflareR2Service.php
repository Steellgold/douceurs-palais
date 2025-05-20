<?php

namespace App\Service;

use Aws\S3\S3Client;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class CloudflareR2Service {
  private S3Client $client;
  private string $bucket;
  private SluggerInterface $slugger;
  private string $accountId;

  public function __construct(
    string           $accountId,
    string           $accessKey,
    string           $secretKey,
    string           $bucket,
    SluggerInterface $slugger
  ) {
    $this->bucket = $bucket;
    $this->slugger = $slugger;
    $this->accountId = $accountId;

    $this->client = new S3Client([
      'version' => 'latest',
      'region' => 'auto',
      'endpoint' => "https://{$accountId}.r2.cloudflarestorage.com",
      'credentials' => [
        'key' => $accessKey,
        'secret' => $secretKey,
      ],
    ]);
  }

  public function uploadFile(UploadedFile $file, string $directory = 'products'): string {
    // Création d'un nom de fichier unique
    $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
    $safeFilename = $this->slugger->slug($originalFilename);
    $fileName = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

    // Upload vers R2
    $result = $this->client->putObject([
      'Bucket' => $this->bucket,
      'Key' => "{$directory}/{$fileName}",
      'Body' => fopen($file->getPathname(), 'rb'),
      'ACL' => 'public-read',
      'ContentType' => $file->getMimeType(),
    ]);

    $imgp = str_replace(
      `https://{$this->accountId}.r2.cloudflarestorage.com`,
      'https://cdn.douceurs-palais.fr/',
      $result['ObjectURL']
    );

    var_dump($imgp);
    dump($result);

    // Retourne l'URL complète du fichier
    return str_replace(
      `https://{$this->accountId}.r2.cloudflarestorage.com`,
      'https://cdn.douceurs-palais.fr/',
      $result['ObjectURL']
    );
  }

  public function deleteFile(string $url): bool {
    // Extraction de la clé depuis l'URL
    $parsedUrl = parse_url($url);
    $path = $parsedUrl['path'] ?? '';

    // La clé est le chemin sans le slash initial
    $key = ltrim($path, '/');

    try {
      $this->client->deleteObject([
        'Bucket' => $this->bucket,
        'Key' => $key,
      ]);

      return true;
    } catch (\Exception $e) {
      return false;
    }
  }
}