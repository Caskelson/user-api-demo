<?php 

namespace App\Form;

use App\Form\UserType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UpdateUserType extends UserType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'is_edit' => true,
        ]);
    }
}