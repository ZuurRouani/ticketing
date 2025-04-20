<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\Ticket;
use App\Entity\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class TicketFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // ->add('owner_id')
            // ->add('assigned_to_id')
            // ->add('category_id')
            ->add('title', TextType::class, [
                'label'=>'Label',
                'attr' => [
                    'class' => 'form-control',
              ],
            ])

            ->add('description', TextareaType::class, [
                'label'=>'Description',
                'attr' => [
                    'class' => 'form-control',
              ],
            ])
            // ->add('status')
            ->add('priority', ChoiceType::class, [
                'label' => 'Priority',
                'choices' => [
                'Low' => 'Low',
                'Medium' => 'Medium',
                'High' => 'High',
                ],
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            // ->add('created_at', null, [
            //     'widget' => 'single_text',
            // ])
            // ->add('updated_at', null, [
            //     'widget' => 'single_text',
            // ])
            // ->add('owner', EntityType::class, [
            //     'class' => User::class,
            //     'choice_label' => 'id',
            // ])
            // ->add('assigned', EntityType::class, [
            //     'class' => User::class,
            //     'choice_label' => 'id',
            // ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'label'=>'Category',
                'attr' => [
                    'class' => 'form-control',
              ],
            ])
            ->add('attachments', FileType::class, [
                'label' => 'attachments (Photos/Documents)',
                'mapped' => false,
                'required' => false,
                'multiple' => true,
                'constraints' => [
                        new NotBlank(['message' => 'Please upload at least one file.']),
                ],
                 'attr' => [
                       'class' => 'form-control',
                       'multiple' => 'multiple',
                 ],
                ]);

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ticket::class,
        ]);
    }
}
