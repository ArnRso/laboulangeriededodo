<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        $user = $this->getUser();

        if ($user instanceof User) {
            return $this->redirectToRoute($user->isAdmin() ? 'app_admin_pack_index' : 'app_journey');
        }

        return $this->render('home/index.html.twig');
    }
}
