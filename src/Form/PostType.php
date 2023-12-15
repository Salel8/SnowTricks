<?php 
// src/Form/PostType.php
namespace App\Form;

use App\Entity\Post;
use App\Entity\Picture_post;
use App\Entity\Video_post;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\All;

class PostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class)
            ->add('description', TextareaType::class)
            ->add('group_figure', TextType::class)
            //->add('video', TextType::class)
            ->add('video', TextType::class, [
                'mapped' => false,

                'required' => false
                ])
            ->add('picture', FileType::class, [
                'label' => 'Picture (JPEG ou PNG file)',

                'multiple' => true,

                // unmapped means that this field is not associated to any entity property
                'mapped' => false,

                // make it optional so you don't have to re-upload the PDF file
                // every time you edit the Product details
                'required' => false,

                // unmapped fields can't define their validation using annotations
                // in the associated entity, so you can use the PHP constraint classes
                'constraints' => [
                    new All([
                        'constraints' => [
                            new File([
                                'maxSize' => '1024k',
                                'mimeTypes' => [
                                    //'application/pdf',
                                    //'application/x-pdf',
                                    'image/jpeg',
                                    'image/png',
                                ],
                                'mimeTypesMessage' => 'Please upload a valid Picture',
                            ])
                        ]
                    ])
                ],
            ])
            /*->add('video', FileType::class, [
                'label' => 'Video (PDF file)',

                'multiple' => true,

                // unmapped means that this field is not associated to any entity property
                'mapped' => false,

                // make it optional so you don't have to re-upload the PDF file
                // every time you edit the Product details
                'required' => false,

                // unmapped fields can't define their validation using annotations
                // in the associated entity, so you can use the PHP constraint classes
                'constraints' => [
                    new All([
                        'constraints' => [
                            new File([
                                'maxSize' => '500Mi',
                                'mimeTypes' => [
                                    //'application/pdf',
                                    //'application/x-pdf',
                                    'video/mp4',
                                ],
                                'mimeTypesMessage' => 'Please upload a valid MP4 video',
                            ])
                        ],
                    ])
                ]
            ])*/
            //->add('video', FileType::class, [
                //'label' => 'Video (PDF file)',
                //'multiple' => true,
                // unmapped means that this field is not associated to any entity property
                //'mapped' => false,
                // make it optional so you don't have to re-upload the PDF file
                // every time you edit the Product details
                //'required' => false,
                // unmapped fields can't define their validation using annotations
                // in the associated entity, so you can use the PHP constraint classes
                //'constraints' => [
                    //new File([
                        //'maxSize' => '1024k',
                        //'mimeTypes' => [
                            //'application/pdf',
                            //'application/x-pdf',
                            //'video/mp4',
                        //],
                        //'mimeTypesMessage' => 'Please upload a valid PDF document',
                    //])
                //],
            //])
        ;
    }
    
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Post::class,
            // enable/disable CSRF protection for this form
            'csrf_protection' => true,
            // the name of the hidden HTML field that stores the token
            'csrf_field_name' => '_token',
            // an arbitrary string used to generate the value of the token
            // using a different string for each form improves its security
            'csrf_token_id'   => 'task_item',
        ]);
    }
}