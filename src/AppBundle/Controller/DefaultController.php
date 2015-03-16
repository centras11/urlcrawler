<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\JsonResponse;

class DefaultController extends Controller
{
    /**
     * @Route("/app/example", name="homepage")
     */
    public function indexAction()
    {
        return $this->render('default/index.html.twig');
    }

    /**
     * @param string $link
     * @param string $search
     *
     * @Route("/app/crawler/{link}/{search}", defaults={"link" = "http://12handyorten.com/", "search" = "Impressum;Kontakt"}, name="_crawler", requirements={"link"=".+"})
     *
     * @return JsonResponse
     */
    public function crawlerAction($link, $search)
    {
        // options to use curl with proxy
        /*$options = array(
            'CURLOPT_NOBODY'=> TRUE,
            'CURLOPT_PROXY' => 'http://123.45.xxx.xxx',
            'CURLOPT_PROXYPORT' => '9090',
            'CURLOPT_PROXYUSERPWD' => 'dummyUsername:dummyPassword'
        );*/

        //$curl = $this->get('anchovy.curl')->setURL($link)->setOptions($options)->execute();

        $curl = $this->get('anchovy.curl')->setURL($link)->execute();

        $crawler = new Crawler($curl, $link);

        $data = [];
        $data['title'] = $crawler->filter('title')->text();

        $data['metaTags'] = $crawler
            ->filter('head > meta')
            ->extract(array('name', 'content'))
        ;

        $data['h1'] = $this->filterHTag('h1', $crawler);
        $data['h2'] = $this->filterHTag('h2', $crawler);
        $data['h3'] = $this->filterHTag('h3', $crawler);

        $search = explode(';', $search);
        $links = [];

        foreach ($search as $value) {
            $link = $crawler->selectLink($value);

            if (count($link) != 0) {
                 $links[] = $link->link()->getUri();
            }
        }

        $data['links'] = $links;

        return new JsonResponse($data);
    }

    /**
     * @param string $hTag
     * @param Crawler $crawler
     *
     * @return array
     */
    private function filterHTag($hTag, Crawler $crawler)
    {
        return $crawler->filter($hTag)->extract(['_text']);
    }
}