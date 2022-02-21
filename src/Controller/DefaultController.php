<?php

namespace App\Controller;

use App\Handler\YtDlpHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    private YtDlpHandler $ytDlpHandler;

    public function __construct(YtDlpHandler $ytDlpHandler)
    {
        $this->ytDlpHandler = $ytDlpHandler;
    }

    /**
     * @param Request $request
     * @return Response
     * @Route("/", name="home")
     */
    public function index(Request $request): Response
    {
        $form = $this->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $url = $form->getData()['url'];

            if ($form->get('submitVideo')->isClicked()) {
                return $this->downloadVideo($url);
            } elseif ($form->get('submitAudio')->isClicked()) {
                return $this->downloadAudio($url);
            }
        }

        return $this->render('Default/index.html.twig', ['form' => $form->createView()]);
    }

    private function getForm(): FormInterface
    {
        return $this->createFormBuilder(null, ['method' => 'POST'])
            ->add('url', UrlType::class, ['label' => false, 'attr' => ['placeholder' => 'URL']])
            ->add('submitVideo', SubmitType::class, ['label' => 'Vidéo', 'attr' => ['class' => 'btn-warning']])
            ->add('submitAudio', SubmitType::class, ['label' => 'Audio', 'attr' => ['class' => 'btn-primary']])
            ->getForm()
        ;
    }

    private function downloadVideo(string $url): Response
    {
        $file = $this->ytDlpHandler->extractVideo($url);

        return $this->file($file, basename($file));
    }

    private function downloadAudio(string $url): Response
    {
        $file = $this->ytDlpHandler->extractAudio($url);

        return $this->file($file, basename($file));
    }
}
