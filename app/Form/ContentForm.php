<?php

// src/Forms/ContentFormType.php
namespace App\Forms;

use App\Entity\Content;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
#use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
#use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
#use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

use FOS\CKEditorBundle\Form\Type\CKEditorType;

class ContentForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        
        $builder    ->add('subjectid', TextType::class);  
      #    $builder    ->add('language', TextType::class);  
       $builder    ->add('language', ChoiceType::class, array(
        'choices'  => array(
         'fr' => 'fr',
         'en' => 'en',
        '*' => '*', ),
            ));
        $builder    ->add('title', TextType::class);
        $builder->add('text', CKEditorType::class, array( 'config'=>array('config_name'=> 'my_config',),));
   }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Content::class,
        ));
    }
    
    
    
}
