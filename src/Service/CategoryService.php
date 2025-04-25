<?php

namespace App\Service;

use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;

class CategoryService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getAllCategories(): array
    {
        return $this->entityManager->getRepository(Category::class)->findAll();
    }

    public function findCategoryById(int $id): ?Category
    {
        return $this->entityManager->getRepository(Category::class)->find($id);
    }

    public function createCategory(Category $category): void
    {
        $this->entityManager->persist($category);
        $this->entityManager->flush();
    }

    public function updateCategory(Category $category, string $name): void
    {
        $category->setName($name);
        $this->entityManager->flush();
    }

    public function deleteCategory(Category $category): void
    {
        $this->entityManager->remove($category);
        $this->entityManager->flush();
    }
}
