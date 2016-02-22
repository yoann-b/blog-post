<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Blogpost;
use AppBundle\Entity\Concerts;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class DefaultController extends Controller
{

    /**
     * @Route("/home", name="homepage")
     */
    public function indexAction(Request $request)
    {
        // replace this example code with whatever you need
        return $this->render('default/index.html.twig', [
            'base_dir' => realpath($this->container->getParameter('kernel.root_dir').'/..'),
        ]);
    }

    /**
     * @Route("/", name="home")
     */
    public function homeAction()
    {
        $sponsor = array(
            "bundles/app/images/sponsor/sponsor1.png",
            "bundles/app/images/sponsor/sponsor2.png",
            "bundles/app/images/sponsor/sponsor3.png",
            "bundles/app/images/sponsor/sponsor4.png",
            "bundles/app/images/sponsor/sponsor5.png",
            "bundles/app/images/sponsor/sponsor6.png"
        );
        return $this->render('AppBundle:default:home.html.twig', array(
            'sponsor' => array_reverse($sponsor),
            'sponsor_reverse' => $sponsor,
        ));
    }

    /**
     * @Route("/page-inexistante", name="page_inexistante")
     */
    public function pageInexistanteAction()
    {
        return $this->render('AppBundle:backoffice:page_inexistante.html.twig');
    }

    /**
     * @Route("/new-post", name="create")
     */
    public function createAction(Request $request)
    {
        // create a task and give it some dummy data for this example
        $blogpost = new Blogpost();

        $form = $this->createFormBuilder($blogpost)
            ->add('title', TextType::class)
            ->add('slug', TextType::class)
            ->add('content', TextareaType::class)
            ->add('author', TextType::class)
            ->add('published', CheckboxType::class)
            ->add('save', SubmitType::class, array('label' => 'Publier le billet'))
            ->getForm();
        ;

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $blogpost->setNbVues(0);
            $em = $this->getDoctrine()->getManager();
            $em->persist($blogpost);
            $em->flush();

            return $this->redirect($this->generateUrl(
                'blog_show',
                array('slug' => $blogpost->getSlug())
            ));
        }

        return $this->render('AppBundle:backoffice:create.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/tous-les-posts", name="posts")
     */
    public function postsAction(Request $request)
    {
        $search = $request->query->get('search');

        $repository = $this
          ->getDoctrine()
          ->getManager()
          ->getRepository('AppBundle:Blogpost')
        ;

        $save = $search ;

        $search = ( $search === false ) ? '%' : '%'.$search.'%' ;

        $listBlogpost = $repository->createQueryBuilder('o')
            ->where('o.published = :published')
            ->andWhere('o.author LIKE :search OR o.content LIKE :search OR o.title LIKE :search')
            ->setParameter('published', 1)
            ->setParameter('search', $search)
            ->addOrderBy('o.updated', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('AppBundle:default:posts.html.twig', array(
            'listBlogpost' => array_reverse($listBlogpost),
            'recherche' => $save,
        ));
    }

    /**
     * @Route("/billet-de-blog", name="post")
     */
    public function postAction(Request $request)
    {
        $id = $request->query->get('id');

        if($id === null){
            return $this->redirect($this->generateUrl(
                'page_inexistante'
            ));
        }else{

            $repository = $this
              ->getDoctrine()
              ->getManager()
              ->getRepository('AppBundle:Blogpost')
            ;

            $blogpost = $repository->find($id);

            if($blogpost === false){
                return $this->redirect($this->generateUrl(
                    'page_inexistante'
                ));
            }
            $em = $this->getDoctrine()->getManager();
            $nbVues = intval(($blogpost->getNbVues()) + 1) ;
            $blogpost->setNbVues($nbVues);
            $em->flush();

            return $this->render('AppBundle:default:post.html.twig', array(
                'blogpost' => $blogpost,
            ));
        }
    }

    /**
     * @Route("/billet/{slug}", name="blog_show")
     */
    public function showAction($slug)
    {

        if($slug === null){
            return $this->redirect($this->generateUrl(
                'page_inexistante'
            ));
        }else{

            $repository = $this
              ->getDoctrine()
              ->getManager()
              ->getRepository('AppBundle:Blogpost')
            ;

            $blogpost = $repository->findOneBy(
                array('slug' => $slug)
            );

            if($blogpost === false){
                return $this->redirect($this->generateUrl(
                    'page_inexistante'
                ));
            }
            $em = $this->getDoctrine()->getManager();
            $nbVues = intval(($blogpost->getNbVues()) + 1) ;
            $blogpost->setNbVues($nbVues);
            $em->flush();

            return $this->render('AppBundle:default:post.html.twig', array(
                'blogpost' => $blogpost,
            ));
        }
    }

    /**
     * @Route("/billet-de-blog/remove", name="remove")
     */
    public function updateAction(Request $request)
    {
        $id = $request->query->get('id');

        $em = $this->getDoctrine()->getManager();
        $blogpost = $em->getRepository('AppBundle:Blogpost')->find($id);

        if($blogpost === false){
            return $this->redirect($this->generateUrl(
                'page_inexistante'
            ));
        }

        $blogpost->setPublished(0);
        $em->flush();

        return $this->redirectToRoute('posts');
    }

    /**
     * @Route("/billet-de-blog/modify", name="modify")
     */
    public function modifierPostAction(Request $request)
    {
        $id = $request->query->get('id');
        if($id === false){
            return $this->redirect($this->generateUrl(
                'page_inexistante'
            ));
        }else{

            $repository = $this
              ->getDoctrine()
              ->getManager()
              ->getRepository('AppBundle:Blogpost')
            ;
            $blogpost = $repository->find($id);

            if($blogpost === false){
                return $this->redirect($this->generateUrl(
                    'page_inexistante'
                ));
            }
            $form = $this->createFormBuilder($blogpost)
                ->add('title', TextType::class)
                ->add('slug', TextType::class)
                ->add('content', TextareaType::class)
                ->add('author', TextType::class)
                ->add('published', CheckboxType::class)
                ->add('save', SubmitType::class, array('label' => 'Ajouter la modification'))
                ->getForm();
            ;
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $blogpost->setUpdated(new \Datetime()) ;
                $em = $this->getDoctrine()->getManager();
                $em->persist($blogpost);
                $em->flush();

                $subject = "Billet de blog mis à jour - ".$blogpost->getTitle();
                $body = 'Ce billet de blog a été mis à jour le '.date_format($blogpost->getUpdated(), 'd/m/Y');

                $message = \Swift_Message::newInstance()
                    ->setSubject($subject)
                    ->setFrom('yoann.braie@gmail.com')
                    ->setTo('yoann.braie@gmail.com')
                    ->setBody($body)
                ;
                $this->get('mailer')->send($message);

                return $this->redirect($this->generateUrl(
                    'blog_show',
                    array('slug' => $blogpost->getSlug())
                ));
            }

            return $this->render('AppBundle:backoffice:modify.html.twig', array(
                'blogpost' => $blogpost,
                'form' => $form->createView(),
            ));
        }
    }

    /**
     * @Route("/tous-les-concerts", name="concert")
     */
    public function concertAction(Request $request)
    {
        $search = $request->query->get('search');

        $repository = $this
          ->getDoctrine()
          ->getManager()
          ->getRepository('AppBundle:Concerts')
        ;

        $save = $search ;

        $search = ( $search === false ) ? '%' : '%'.$search.'%' ;

        $listConcerts = $repository->createQueryBuilder('o')
            ->where('o.published = :published')
            ->setParameter('published', 1)
            ->getQuery()
            ->getResult();

        return $this->render('AppBundle:default:concerts.html.twig', array(
            'concerts' => $listConcerts,
        ));
    }

    /**
     * @Route("/concert", name="ticket")
     */
    public function ticketConcertAction(Request $request)
    {
        $id = $request->query->get('id');

        if($id === null){
            return $this->redirect($this->generateUrl(
                'page_inexistante'
            ));
        }else{

            $repository = $this
              ->getDoctrine()
              ->getManager()
              ->getRepository('AppBundle:Concerts')
            ;

            $concert = $repository->find($id);

            if($concert === false){
                return $this->redirect($this->generateUrl(
                    'page_inexistante'
                ));
            }
            $em = $this->getDoctrine()->getManager();
            $em->flush();

            return $this->render('AppBundle:default:ticket_concert.html.twig', array(
                'concert' => $concert,
            ));
        }
    }

    /**
     * @Route("/concert/remove", name="remove_concert")
     */
    public function updateConcertAction(Request $request)
    {
        $id = $request->query->get('id');

        $em = $this->getDoctrine()->getManager();
        $concert = $em->getRepository('AppBundle:Concerts')->find($id);

        if($concert === false){
            return $this->redirect($this->generateUrl(
                'page_inexistante'
            ));
        }

        $concert->setPublished(0);
        $em->flush();

        return $this->redirectToRoute('concert');
    }

    /**
     * @Route("/ajouter-concert", name="create_concert")
     */
    public function createConcertAction(Request $request)
    {
        // create a task and give it some dummy data for this example
        $concert = new Concerts();

        $form = $this->createFormBuilder($concert)
            ->add('title', TextType::class)
            ->add('artist', TextType::class, array('label' => 'Artists (use commas as separators)'))
            ->add('description', TextAreaType::class)
            ->add('date', DateType::class)
            ->add('places', IntegerType::class)
            ->add('save', SubmitType::class, array('label' => 'Publier le concert'))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($concert);
            $em->flush();

            return $this->redirect($this->generateUrl(
                'ticket',
                array('id' => $concert->getId())
            ));
        }

        return $this->render('AppBundle:backoffice:create_concert.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/concert/modify", name="modify_concert")
     */
    public function modifierConcertAction(Request $request)
    {
        $id = $request->query->get('id');
        if($id === false){
            return $this->redirect($this->generateUrl(
                'page_inexistante'
            ));
        }else{

            $repository = $this
              ->getDoctrine()
              ->getManager()
              ->getRepository('AppBundle:Concerts')
            ;
            $concert = $repository->find($id);

            if($concert === false){
                return $this->redirect($this->generateUrl(
                    'page_inexistante'
                ));
            }
            $form = $this->createFormBuilder($concert)
                ->add('title', TextType::class)
                ->add('artist', TextType::class, array('label' => 'Artists (use commas as separators)'))
                ->add('description', TextAreaType::class)
                ->add('date', DateType::class)
                ->add('places', IntegerType::class)
                ->add('save', SubmitType::class, array('label' => 'Publier le concert'))
                ->getForm();
            ;
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $em->persist($concert);
                $em->flush();

                return $this->redirect($this->generateUrl(
                    'concert',
                    array('id' => $concert->getId())
                ));
            }

            return $this->render('AppBundle:backoffice:modify_concert.html.twig', array(
                'concert' => $concert,
                'form' => $form->createView(),
            ));
        }
    }

    /**
     * @Route("/concert/validate", name="valide_concert")
     */
    public function acheterConcertAction(Request $request)
    {
        $id = $request->query->get('id');
        $places = $request->query->get('nbplaces');
        if($id === false){
            return $this->redirect($this->generateUrl(
                'page_inexistante'
            ));
        }else{

            $repository = $this
              ->getDoctrine()
              ->getManager()
              ->getRepository('AppBundle:Concerts')
            ;
            $concert = $repository->find($id);

            if($concert === false){
                return $this->redirect($this->generateUrl(
                    'page_inexistante'
                ));
            }else{
                $em = $this->getDoctrine()->getManager();
                $concert->setPlaces( $concert->getPlaces() - $places );
                $em->flush();

                return $this->render('AppBundle:default:remerciements.html.twig', array(
                    'concert' => $concert,
                ));          
            }
        }
    }

}
