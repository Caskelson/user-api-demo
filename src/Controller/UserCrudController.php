<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Form\UpdateUserType;
use Symfony\Component\Form\Form;
use App\Traits\JsonHandlingTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserCrudController extends AbstractController
{
    use JsonHandlingTrait;

    public function indexAction(SerializerInterface $serializer): Response
    {
        $users = $this->getDoctrine()->getRepository(User::class)->findAll();

        return $this->jsonResponseHandler(
            $serializer,
            $this->getJsonDefaultMessage("val_suc", $users),
            Response::HTTP_OK,
            [ObjectNormalizer::GROUPS => ['user']]
        );
    }

    public function createAction(Request $request, UserPasswordHasherInterface $passwordHasher, SerializerInterface $serializer): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);

        $form->submit(
            array_merge(
                $this->getJsonFromRequest($request), 
                $request->request->all()
            )
        );

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->jsonResponseHandler(
                $serializer,
                $this->getJsonDefaultMessage("val_err", $this->getValidationErrors($form)),
                Response::HTTP_BAD_REQUEST,
            );
        }
        
        $user = $form->getData();
        $user->setPassword($passwordHasher->hashPassword($user, $user->getPassword()));
        $this->getDoctrine()->getManager()->persist($user);
        $this->getDoctrine()->getManager()->flush();

        return $this->jsonResponseHandler(
            $serializer,
            $this->getJsonDefaultMessage("val_suc", $user),
            Response::HTTP_CREATED,
            [ObjectNormalizer::GROUPS => ['user']]
        );
    }

    public function updateAction(Request $request, UserPasswordHasherInterface $passwordHasher, SerializerInterface $serializer): Response
    {
        $email = $request->headers->get('Email');
        $data = $this->getJsonFromRequest($request);
            
        if ($request->isMethod('PATCH')) {
            $clearMissing = false;

            if (array_key_exists('roles', $data))
                unset($data['roles']);

            $user = $this->getDoctrine()->getRepository(User::class)->findOneByEmail($email);
            $form = $this->createForm(UpdateUserType::class, $user);
        }
        else {
            $clearMissing = true;

            if (!array_key_exists('email', $data)) {
                return $this->jsonResponseHandler(
                    $serializer,
                    $this->getJsonDefaultMessage("val_err", "Missing email field indicating which user to edit"),
                    Response::HTTP_BAD_REQUEST
                );
            }
            $user = $this->getDoctrine()->getRepository(User::class)->findOneByEmail($data['email']);
            $form = $this->createForm(UserType::class, $user);
        }

        $form->submit(
            array_merge(
                $data, 
                $request->request->all()
            ),
            $clearMissing
        );

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->jsonResponseHandler(
                $serializer,
                $this->getJsonDefaultMessage("val_err", $this->getValidationErrors($form)),
                Response::HTTP_BAD_REQUEST,
            );
        }

        $user->setPassword($passwordHasher->hashPassword($user, $user->getPassword()));
        $this->getDoctrine()->getManager()->persist($user);
        $this->getDoctrine()->getManager()->flush();

        return $this->jsonResponseHandler(
            $serializer,
            $this->getJsonDefaultMessage("val_suc", $user),
            Response::HTTP_OK,
            [ObjectNormalizer::GROUPS => ['user']]
        );
    }

    public function deleteAction(Request $request, SerializerInterface $serializer): Response
    {
        $data = $this->getJsonFromRequest($request);

        if (!array_key_exists('email', $data)) {
            return $this->jsonResponseHandler(
                $serializer,
                $this->getJsonDefaultMessage("val_err", "Missing email field indicating which user to delete"),
                Response::HTTP_BAD_REQUEST
            );
        }

        $user = $this->getDoctrine()->getRepository(User::class)->findOneByEmail($data['email']);

        if ($user) {
            $this->getDoctrine()->getManager()->remove($user);
            $this->getDoctrine()->getManager()->flush();
        }

        return $this->jsonResponseHandler(
            $serializer,
            [],
            Response::HTTP_NO_CONTENT
        );
    }

    private function getValidationErrors(Form $form): array
    {
        $error_list = [];
        $errorIterator = $form->getErrors(true, false);

        foreach($errorIterator as $errors) {
            foreach($errors as $error) {
                $error_list[$errors->getForm()->getName()][] = $error->getMessage();
            }
        }

        return $error_list;
    }
}
