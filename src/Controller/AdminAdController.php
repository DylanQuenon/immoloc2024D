<?php

namespace App\Controller;

use App\Entity\Ad;
use App\Form\AnnonceType;
use App\Repository\AdRepository;
use App\Service\PaginationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AdminAdController extends AbstractController
{
    /**
     * Permet d'afficher l'ensemble des annonces
     *
     * @param PaginationService $pagination
     * @return Response
     */
    #[Route('/admin/ads/{page<\d+>?1}', name: 'admin_ads_index')]
    public function index(PaginationService $pagination, int $page): Response
    {
        $pagination->setEntityClass(Ad::class) // App\Entity\Ad string
                ->setPage($page)
                ->setLimit(10);
       

        return $this->render('admin/ad/index.html.twig', [
           'pagination' => $pagination
        ]);
    }

    /**
     * Permet d'afficher le formulaire d'édition
     *
     * @param Ad $ad
     * @param Request $request
     * @param EntityManagerInterface $manager
     * @return Response
     */
    #[Route("/admin/ads/{id}/edit", name: "admin_ads_edit")]
    public function edit(Ad $ad, Request $request, EntityManagerInterface $manager): Response
    {
        $form = $this->createForm(AnnonceType::class, $ad);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            $manager->persist($ad);
            $manager->flush();

            $this->addFlash(
                'success',
                "L'annonce <strong>".$ad->getTitle()."</strong> a bien été modifiée"
            );

        }

        return $this->render("admin/ad/edit.html.twig",[
            "ad" => $ad,
            "myForm" => $form->createView()
        ]);

    }

    /**
     * Permet de supprimer une annonce
     *
     * @param Ad $ad
     * @param EntityManagerInterface $manager
     * @return Response
     */
    #[Route("/admin/ads/{id}/delete", name: "admin_ads_delete")]
    public function delete(Ad $ad, EntityManagerInterface $manager): Response
    {
        // on en peut pas supprimer une annonce qui possède des réservations
        if(count($ad->getBookings()) > 0)
        {
            $this->addFlash(
                'warning',
                "Vous ne pouvez pas supprimer l'annonce <strong>".$ad->getTitle()."</strong> car elle possède des réservations"
            );
        }else{
            $this->addFlash(
                "success",
                "L'annonce <strong>".$ad->getTitle()."</strong> a bien été supprimée"
            );
            $manager->remove($ad);
            $manager->flush();
        }

        return $this->redirectToRoute('admin_ads_index');
    }

}