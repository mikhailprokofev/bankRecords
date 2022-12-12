<?php

declare(strict_types=1);

namespace App\Controller;

const FILE_FOLDERS = './files/';

use App\Message\BankRecordsFile; 
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class UploadController extends DefaultController
{
    private string $fileName;

    public function __construct(
        private readonly MessageBusInterface $bus,
    ) {
    }

    #[Route('/api/upload', name:'csv')]
    public function csv(Request $request): JsonResponse
    {
        $file = $request->files->get('file');
        $this->fileName = $file->getClientOriginalName();
        $this->saveFile($file);
        $this->bus->dispatch(new BankRecordsFile($this->fileName));
        return $this->json('Файл получен', 200);
    }

    public function saveFile(object $file): void
    {
        try {
            $file->move(FILE_FOLDERS, $this->fileName);
        } catch (FileException $e) {
            dd($e);
        }
    }
}