<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\JsonResponse;

class DefaultController extends Controller
{
    /**
     * @param string $link
     *
     * @Route("/app/crawler/{link}", defaults={"link" = "http://12handyorten.com/"}, name="_crawler", requirements={"link"=".+"})
     *
     * @return JsonResponse
     */
    public function crawlerAction($link)
    {
        $curl = $this->getCrawlerManager()->getCurl($link);

        $crawler = new Crawler($curl, $link);

        $data = [];
        $data['title'] = $crawler->filter('title')->text();

        $data['metaTags'] = $crawler
            ->filter('head > meta')
            ->extract(array('name', 'content'))
        ;

        $data['h1'] = $this->getCrawlerManager()->filterHTag('h1', $crawler);
        $data['h2'] = $this->getCrawlerManager()->filterHTag('h2', $crawler);
        $data['h3'] = $this->getCrawlerManager()->filterHTag('h3', $crawler);

        $search = $this->container->getParameter('crawler.search_link_names');
        $links = [];

        foreach ($search as $value) {
            $link = $crawler->selectLink($value);
            //$link = $crawler->filter('a:contains("Greet")')->eq(1)->link();

            if (count($link) != 0) {
                 $links[] = $link->link()->getUri();
            }
        }

        $data['links'] = $links;

        $emails = $phones = [];

        foreach ($links as $link) {
            $emails = array_merge($emails, $this->getCrawlerManager()->crawlerByRule('email', $link));
            $phones = array_merge($phones, $this->getCrawlerManager()->crawlerByRule('phone', $link));
        }

        $data['emails'] = array_unique($emails);
        $data['phones'] = array_unique($phones);

        return new JsonResponse($data);
    }

    // http://localhost/crawler/web/app_dev.php/app/crawler-by-rule/email/http://www.impuls.lt/kontaktai/
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
        $curl = $this->getCrawlerManager()->getCurl($link);

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
     * @return \AppBundle\Service\CrawlerManager
     */
    protected function getCrawlerManager()
    {
        return $this->get('crawler.service.crawler_manager');
    }
}
