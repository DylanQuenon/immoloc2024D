<?php

namespace App\Controller;

use App\Entity\Ad;
use App\Entity\Image;
use App\Form\AnnonceType;
use App\Repository\AdRepository;
use App\Service\PaginationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AdController extends AbstractController
{

    /**
     * Permet d'afficher l'ensemble des annonces du site
     *
     * @param AdRepository $repo
     * @return Response
     */
    #[Route('/ads/{page<\d+>?1}', name: 'ads_index')]
    public function index(PaginationService $pagination, int $page): Response
    {

        $pagination->setEntityClass(Ad::class) // App\Entity\Ad string
        ->setPage($page)
        ->setLimit(10);

        return $this->render('ad/index.html.twig', [
            'pagination' => $pagination
        ]);
    }


    /**
     * Permet d'ajouter une annonce à la bdd 
     *
     * @return Response
     */
    #[Route("/ads/new", name:"ads_create")]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request, EntityManagerInterface $manager): Response
    {

        $ad = new Ad();
  


        $form = $this->createForm(AnnonceType::class, $ad);

        // $arrayForm = $request->request->all();
        // dump($arrayForm);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {

            //gestion des images
            foreach($ad->getImages() as $image)
            {
                $image->setAd($ad);
                $manager->persist($image);
            }
            $ad->setAuthor($this->getUser());

            // je persiste mon objet Ad
            $manager->persist($ad);
            // j'envoie les persistances dans la bdd
            $manager->flush();

            $this->addFlash('success', "L'annonce <strong>".$ad->getTitle()."</strong> a bien été enregistrée");

            return $this->redirectToRoute('ads_show',[
                'slug' => $ad->getSlug()
            ]);

        }

        return $this->render("ad/new.html.twig",[
            'myForm' => $form->createView()
        ]);

    }

     /**
     * Permet d'éditier une annonce
     *
     * @param Request $request
     * @param EntityManagerInterface $manager
     * @param Ad $ad
     * @return Response
     */
    #[Route("/ads/{slug}/edit", name:"ads_edit")]
    #[IsGranted(
        attribute:new Expression('(user === subject and is_granted("ROLE_USER")) or is_granted("ROLE_ADMIN")'),
        subject:new Expression('args["ad"].getAuthor()'),//l'annonce
        message:"Cette annonce ne peut pas être modifiée"
    )]
    public function edit(Request $request, EntityManagerInterface $manager, Ad $ad): Response

    {
        $form = $this->createForm(AnnonceType::class, $ad);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            //si je veux que le slug soit automatique
            $ad->setSlug("");

              // gestion des images 
              foreach($ad->getImages() as $image)
              {
                  $image->setAd($ad);
                  $manager->persist($image);
              }

              $manager->persist($ad);
              $manager->flush();

              $this->addFlash(
                'success',
                "L'annonce <strong>".$ad->getTitle()."</strong> a bien été modifiée!"
              );

              return $this->redirectToRoute('ads_show',[
                'slug' => $ad->getSlug()
              ]);
  
        }


        return $this->render("ad/edit.html.twig", [
            "ad" => $ad,
            "myForm" => $form->createView()
        ]);
    }
    /**
     * -Permet de supprimer une annonce
     *
     * @param Ad $ad
     * @param EntityManagerInterface $manager
     * @return Response
     */
    #[Route('/ads/{slug}/delete', name:"ads_delete")]
    #[IsGranted(
        attribute:new Expression('(user === subject and is_granted("ROLE_USER")) or is_granted("ROLE_ADMIN")'),
        subject:new Expression('args["ad"].getAuthor()'),//l'annonce
        message:"Cette annonce ne vous appartient pas elle ne peut pas être supprimée"
    )]
    public function delete(Ad $ad, EntityManagerInterface $manager): Response
    {
        $this->addFlash(
            "success",
            "L'annonce <strong>".$ad->getTitle()."</strong> a bien été supprimée"
        );
        $manager->remove($ad);
        $manager->flush();
        return $this->redirectToRoute('ads_index');

    }


    /**
     * Permet d'afficher une annonce 
     * 
     * @param string $slug
     * @param Ad $ad
     * @return Response
     */
    #[Route("/ads/{slug}", name:"ads_show")]
    public function show(string $slug, Ad $ad): Response
    {
        // $ad = $repo->findby(["slug"=>$slug])

        // dump($ad);

        return $this->render("ad/show.html.twig", [
            'ad' => $ad
        ]);
    }


}