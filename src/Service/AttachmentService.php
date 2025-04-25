<?php

namespace App\Service;

use App\Entity\Attachment;
use App\Entity\Ticket;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class AttachmentService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function addAttachment(Ticket $ticket, UploadedFile $uploadedFile, string $uploadsDirectory): Attachment
    {
        $filename = uniqid() . '.' . $uploadedFile->guessExtension();
        $filePath = $uploadsDirectory . '/' . $filename;

        $uploadedFile->move($uploadsDirectory, $filename);

        $attachment = new Attachment();
        $attachment->setFileName($filename);
        $attachment->setFilePath($filePath);
        $attachment->setTicket($ticket);

        $this->entityManager->persist($attachment);
        $this->entityManager->flush();

        return $attachment;
    }

    public function deleteAttachment(Attachment $attachment, string $uploadsDirectory): void
    {
        $filePath = $uploadsDirectory . '/' . $attachment->getFileName();

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $this->entityManager->remove($attachment);
        $this->entityManager->flush();
    }

    public function findAttachmentById(int $id): ?Attachment
    {
        return $this->entityManager->getRepository(Attachment::class)->find($id);
    }
}
