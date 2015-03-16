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
        $curl = $this->getCurl($link);

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
            //$link = $crawler->filter('a:contains("Greet")')->eq(1)->link();

            if (count($link) != 0) {
                 $links[] = $link->link()->getUri();
            }
        }

        $data['links'] = $links;


        return new JsonResponse($data);
    }

    /**
     * @param string $rule
     * @param string $link
     *
     * @Route("/app/crawler-by-rule/{rule}/{link}", defaults={"link" = "http://12handyorten.com/", "rule" = "email"}, name="_crawler_by_rule", requirements={"link"=".+"})
     *
     * @return JsonResponse
     */
    public function crawlerByRuleAction($rule, $link)
    {
        $curl = $this->getCurl($link);

        switch ($rule) {
            case 'email':
                $rule = '/([\s]*)([_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*([ ]+|)@([ ]+|)([a-zA-Z0-9-]+\.)+([a-zA-Z]{2,}))([\s]*)/i';
                break;
            case 'phone':
                $rule = '/[8] [0-9]{3} [0-9]{2} [0-9]{3}/';
                break;
        }

        $data = [];
        $match = preg_match_all($rule, $curl, $matches);

        if ($match) {
            foreach($matches[0] as $emails) {
                $data['matches'][] = $emails;
            }
        }

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

    /**
     * @param string  $link
     * @param boolean $useProxy
     *
     * @return mixed
     */
    private function getCurl($link, $useProxy = false)
    {
        $options = [];

        if ($useProxy) {
            $options = [
                'CURLOPT_NOBODY'=> TRUE,
                'CURLOPT_PROXY' => 'http://123.45.xxx.xxx',
                'CURLOPT_PROXYPORT' => '9090',
                'CURLOPT_PROXYUSERPWD' => 'dummyUsername:dummyPassword'
            ];
        }

        return $this->get('anchovy.curl')->setURL($link)->setOptions($options)->execute();
    }
}
