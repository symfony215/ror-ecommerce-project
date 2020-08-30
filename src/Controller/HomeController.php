<?php

namespace App\Controller;

use App\Entity\Admin\Messages;
use App\Entity\Product;
use App\Entity\Setting;
use App\Form\Admin\MessagesType;
use App\Repository\Admin\CommentRepository;
use App\Repository\ImageRepository;
use App\Repository\ProductRepository;
use App\Repository\SettingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Bridge\Google\Smtp\GmailTransport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    /**
     * @Route("/", name="home")
     */
    public function index(SettingRepository $settingRepository,ProductRepository $productRepository)
    {
        $setting = $settingRepository->findAll();
        $slider = $productRepository->findBy([],['title'=>'ASC'],4); //slider verilerini buradan çeker.
        $products = $productRepository->findBy([],['title'=>'DESC'],3);
        $newproducts = $productRepository->findBy([],['title'=>'DESC'],4);
        $new2products = $productRepository->findBy([],['title'=>'DESC'],10);
        // array findBy(array $criteria, array|null $orderBy = null, int|null $limit = null, int|null $offset = null)
        // dump($setting);
        //die();
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'setting'=> $setting,
            'slider'=>$slider,
            'products'=>$products,
            'newproducts'=>$newproducts,
            'new2products'=>$new2products,
        ]);
    }

    /**
     * @Route("/product/{id}", name="product_show", methods={"GET"})
     */
    public function show(Product $product,$id,ImageRepository $imageRepository,CommentRepository $commentRepository): Response
    {
         $images=$imageRepository->findAll();
       // $images=$imageRepository->findBy(['product'=>$id]);
        $comments=$commentRepository->findBy(['productid'=>$id, 'status'=>'True']);

        return $this->render('home/productshow.html.twig', [
            'product' => $product,
           'images'=> $images,
            'comments'=> $comments,
        ]);
    }

    /**
     * @Route("/about", name="home_about")
     */
    public function about(SettingRepository $settingRepository): Response
    {
        $setting = $settingRepository->findAll();
        return $this->render('home/aboutus.html.twig', [
            'setting' => $setting,

        ]);
    }

    /**
     * @Route("/contact", name="home_contact",methods={"GET","POST"})
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    public function contact(SettingRepository $settingRepository,Request $request): Response
    {

        $message = new Messages();
        $form = $this->createForm(MessagesType::class, $message);
        $form->handleRequest($request);
        $submittedToken=$request->request->get('token');
        $setting=$settingRepository->findAll();
        // dump($request);
        // die();

        if ($form->isSubmitted()) {
            if ($this->isCsrfTokenValid('form-message', $submittedToken)) {
                $entityManager = $this->getDoctrine()->getManager();
                $message->setStatus('New');
                $message->setIp($_SERVER['REMOTE_ADDR']);
                $entityManager->persist($message);
                $entityManager->flush();
                $this->addFlash('success', 'Your message has been sent successfully');

                //********* SEND EMAIL*********>>>>
                $email = (new Email())
                    ->from($setting[0]->getSmtpemail())
                    ->to($form['email']->getData())
                    //->cc('cc@example.com')
                    //->bcc('bcc@example.com')
                    //->replyTo('fabien@example.com')
                    //->priority(Email::PRIORITY_HIGH)
                    ->subject('All Product Your Request')
                    //->text('Sending emails is fun again!')
                    ->html("Dear".$form['name']->getData()."<br>
                       <p>We will evaluate your requests and contact you as soon as possible </p>
                       Thank you for your message<br>
                        =======================
                        <br>".$setting[0]->getBrand()."<br>
                       Adress:".$setting[0]->getAdress()."<br>
                       Phone:".$setting[0]->getPhone()."<br>"

                    );

                $transport=new GmailTransport($setting[0]->getSmtpemail(),$setting[0]->getSmtppassword());
                $mailer=new Mailer($transport);
                $mailer->send($email);
                // $messageId = $sentEmail->getMessageId();

                // <<<<<<<<<*******SEND EMAIL *******>>>>>>>>>>>>


                return $this->redirectToRoute('home_contact');
            }
        }


        $setting = $settingRepository->findAll();
        return $this->render('home/contact.html.twig', [
            'setting' => $setting,
            'form' => $form->createView(),


        ]);
    }

}
