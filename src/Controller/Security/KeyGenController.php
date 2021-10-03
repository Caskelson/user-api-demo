<?php

namespace App\Controller\Security;

use App\Entity\User;
use App\Entity\ApiToken;
use App\Traits\JsonHandlingTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class KeyGenController extends AbstractController
{
    use JsonHandlingTrait;

    public function keygenAction(Request $request, UserPasswordHasherInterface $passwordHasher, RoleHierarchyInterface $hierarchy, SerializerInterface $serializer): Response
    {
        $email = $request->headers->get('Email');
        $password = $request->headers->get('Password');
        
        if ($password === null || $email === null){
            return $this->jsonResponseHandler(
                $serializer,
                $this->getJsonDefaultMessage("auth_err", "Invalid password or email credentials"),
                Response::HTTP_UNAUTHORIZED
            );
        }

        $user = $this->getDoctrine()->getRepository(User::class)->findOneByEmail($email);
        $roles = $hierarchy->getReachableRoleNames($user->getRoles());

        if (!in_array('ROLE_ADMIN', $roles)) {
            return $this->jsonResponseHandler(
                $serializer,
                $this->getJsonDefaultMessage("auth_err", "Invalid permission credentials"),
                Response::HTTP_UNAUTHORIZED
            );
        }

        if (!$passwordHasher->isPasswordValid($user, $password)) {
            return $this->jsonResponseHandler(
                $serializer,
                $this->getJsonDefaultMessage("auth_err", "Invalid password or email credentials"),
                Response::HTTP_UNAUTHORIZED
            );
        }

        $apiToken = new ApiToken($user);
        $this->getDoctrine()->getManager()->persist($apiToken);
        $this->getDoctrine()->getManager()->flush();


        return $this->jsonResponseHandler(
            $serializer,
            $this->getJsonDefaultMessage("auth_suc", $apiToken->getToken())
        );
    }
}
