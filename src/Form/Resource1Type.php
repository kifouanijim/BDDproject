<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Resource;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

class Resource1Type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title')
            ->add('description')
            ->add('url')
            ->add('createdAt', DateTimeType::class, [
                'widget' => 'single_text',
                'required' => false, // Permet d'éviter une erreur si la date est auto-générée
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name', // Afficher le nom au lieu de l'ID
                'placeholder' => 'Sélectionnez une catégorie', // Ajout d'une valeur par défaut
                'required' => false, // Rendre optionnel si nécessaire
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Resource::class,
        ]);
    }
}
