<?php

namespace AppBundle\Service;

use Anchovy\CURLBundle\CURL\Curl;
use Symfony\Component\DomCrawler\Crawler;

/**
 *  Crawler manager object
 *
 */
class CrawlerManager
{
    /**
     * @var Curl
     */
    protected $curl;

    /**
     * @var string
     */
    protected $emailRule;

    /**
     * @var string
     */
    protected $phoneRule;

    /**
     * @param Curl   $curl
     * @param string $emailRule
     * @param string $phoneRule
     */
    public function __construct(Curl $curl, $emailRule, $phoneRule)
    {
        $this->curl = $curl;
        $this->emailRule = $emailRule;
        $this->phoneRule = $phoneRule;
    }

    /**
     * @param string  $link
     * @param boolean $useProxy
     *
     * @return mixed
     */
    public function getCurl($link, $useProxy = false)
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

        return $this->curl->setURL($link)->setOptions($options)->execute();
    }

    /**
     * @param string $rule
     * @param string $link
     *
     * @return array
     */
    public function crawlerByRule($rule, $link)
    {
        $curl = $this->getCurl($link);

        switch ($rule) {
            case 'email':
                $rule = $this->emailRule;
                break;
            case 'phone':
                $rule = $this->phoneRule;
                break;
        }

        $data = [];

        if (preg_match_all($rule, $curl, $matches)) {
            foreach($matches[0] as $match) {
                $data[] = $match;
            }
        }

        return $data;
    }

    /**
     * @param string $hTag
     * @param Crawler $crawler
     *
     * @return array
     */
    public function filterHTag($hTag, Crawler $crawler)
    {
        return $crawler->filter($hTag)->extract(['_text']);
    }
}
