<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Form\UpdateUserType;
use Symfony\Component\Form\Form;
use App\Traits\JsonHandlingTrait;
use PhpParser\Node\Expr\Instanceof_;
use Symfony\Component\Form\FormInterface;
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
        $data = $this->getJsonFromRequest($request);
        
        if ($request->getPathInfo() == '/api/users-collection') {
            return $this->createSeveralUsers($data, $serializer, $passwordHasher);
        } else {
            return $this->createSingleUser($data, $serializer, $passwordHasher);
        }
    }

    public function updateAction(Request $request, UserPasswordHasherInterface $passwordHasher, SerializerInterface $serializer): Response
    {
        $email = $request->headers->get('Email');
        $data = $this->getJsonFromRequest($request);

        if ($request->isMethod('PATCH')) {
            if (!array_key_exists('password', $data) ? true : !$data['password']) {
                return $this->jsonResponseHandler(
                    $serializer,
                    $this->getJsonDefaultMessage("val_err", ["password" => "This field can't be blank"]),
                    Response::HTTP_BAD_REQUEST
                );
            }
            $user = $this->getDoctrine()->getRepository(User::class)->findOneByEmail($email);
            $form = $this->createForm(UpdateUserType::class, $user);
        } else {
            if (!array_key_exists('email', $data)) {
                return $this->jsonResponseHandler(
                    $serializer,
                    $this->getJsonDefaultMessage("val_err", ["email" => "This field can't be blank"]),
                    Response::HTTP_BAD_REQUEST
                );
            }

            $user = $this->getDoctrine()->getRepository(User::class)->findOneByEmail($data['email']);
            if(!$user) {
                return $this->jsonResponseHandler(
                    $serializer,
                    $this->getJsonDefaultMessage("val_err", ["email" => "The email \"" . $data['email'] . "\" doesn't exist."]),
                    Response::HTTP_BAD_REQUEST
                );
            }
            
            $form = $this->createForm(UserType::class, $user);
        }

        if (($result = $this->processForm($form, $data, $serializer)) instanceof Response) {
            return $result;
        }
        
        $em = $this->getDoctrine()->getManager();
        $user->setPassword($passwordHasher->hashPassword($user, $user->getPassword()));
        $em->persist($user);
        $em->flush();

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
                $this->getJsonDefaultMessage("val_err", ["email" => "This field can't be blank"]),
                Response::HTTP_BAD_REQUEST
            );
        }

        $user = $this->getDoctrine()->getRepository(User::class)->findOneByEmail($data['email']);

        if ($user) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($user);
            $em->flush();
        }

        return $this->jsonResponseHandler(
            $serializer,
            [],
            Response::HTTP_NO_CONTENT
        );
    }

    private function createSeveralUsers($data, SerializerInterface $serializer, UserPasswordHasherInterface $passwordHasher): Response
    {
        $successfulUsers = [];
        $errorList = [];
        
        foreach ($data as $key => $userData) {
            $user = new User();
            $form = $this->createForm(UserType::class, $user);

            $form->submit($userData);
            if (!$form->isSubmitted() || !$form->isValid()) {
                $errorList[$key] = $this->getValidationErrors($form);
                continue;
            }

            $successfulUsers[] = $user;
        }

        if (!empty($errorList)) {
            return $this->jsonResponseHandler(
                $serializer,
                $this->getJsonDefaultMessage("val_err", $errorList),
                Response::HTTP_BAD_REQUEST,
            );
        }

        $em = $this->getDoctrine()->getManager();
        foreach ($successfulUsers as $user) {
            $user->setPassword($passwordHasher->hashPassword($user, $user->getPassword()));
            $em->persist($user);
        }
        $em->flush();

        return $this->jsonResponseHandler(
            $serializer,
            $this->getJsonDefaultMessage("val_suc", $successfulUsers),
            Response::HTTP_CREATED,
            [ObjectNormalizer::GROUPS => ['user']]
        );
    }

    private function createSingleUser($data, SerializerInterface $serializer, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        
        if (($result = $this->processForm($form, $data, $serializer)) instanceof Response) {
            return $result;
        }
        
        $em = $this->getDoctrine()->getManager();
        $user->setPassword($passwordHasher->hashPassword($user, $user->getPassword()));
        $em->persist($user);
        $em->flush();

        return $this->jsonResponseHandler(
            $serializer,
            $this->getJsonDefaultMessage("val_suc", $user),
            Response::HTTP_CREATED,
            [ObjectNormalizer::GROUPS => ['user']]
        );
    }

    private function processForm(FormInterface $form, $data, SerializerInterface $serializer): FormInterface|Response
    {
        $form->submit($data);
        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->jsonResponseHandler(
                $serializer,
                $this->getJsonDefaultMessage("val_err", $this->getValidationErrors($form)),
                Response::HTTP_BAD_REQUEST,
            );
        }
        return $form;
    }

    private function getValidationErrors(FormInterface $form): array
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
