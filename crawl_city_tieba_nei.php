<?php
/**
 * Created by PhpStorm.
 * User: lxp1055
 * Date: 2018/4/27
 * Time: 16:55
 */

// It may take a whils to crawl a site ...
set_time_limit(10000);

// Inculde the phpcrawl-mainclass
include("PHPCrawl/libs/PHPCrawler.class.php");
require "simple_html_dom.php";


// Extend the class and override the handleDocumentInfo()-method
class MyCrawler extends PHPCrawler
{
    function handleDocumentInfo($DocInfo)
    {
        // Just detect linebreak for output ("\n" in CLI-mode, otherwise "<br>").
        if (PHP_SAPI == "cli") $lb = "\n";
        else $lb = "<br />";

        // Print the URL and the HTTP-status-Code
        //echo "Page requested: ".$DocInfo->url." (".$DocInfo->http_status_code.")".$lb;

        // Print the refering URL
       // echo "Referer-page: ".$DocInfo->referer_url.$lb;

        // Print if the content of the document was be recieved or not
        if ($DocInfo->received == true){}
            //echo "Content received: " . $DocInfo->bytes_received . " bytes" . $lb;
        else{}
        //echo "Content not received" . $lb;

        // Now you should do something with the content of the actual
        // received page or file ($DocInfo->source), we skip it in this example

        //echo $lb;
        $pant="/http:\/\/tieba\.baidu\.com\/p\/\d+/";
        if(preg_match($pant, $DocInfo->url)>0) {
            echo $DocInfo->url."\n";
            $this->regular_mate_data($DocInfo->content);
        }
        flush();
    }
    function regular_mate_data($content)
    {
        $ma=array();
        preg_match('/<h1 class="core_title_txt .*?>(.*?)<\/h1>/',$content,$ma);
        print_r($ma[1]);
        $maches_aou = array();
        $pat='/<a data-field=.*? class="p_author_name j_user_card.*?>(.*)<\/a>/';
        preg_match_all($pat,$content,$maches_aou);
        print_r($maches_aou[1]);
        $maches_an=array();
        $pant='/<div id="post_content_.*? class="d_post_content j_d_post_content  clearfix.*?>(.*?)<\/div> /';
        preg_match_all($pant,$content,$maches_an);
        print_r($maches_an[1]);
    }
}

// Now, create a instance of your class, define the behaviour
// of the crawler (see class-reference for more options and details)
// and start the crawling-process.

$crawler = new MyCrawler();

// URL to crawl
$crawler->setURL("http://tieba.baidu.com/f?kw=%E5%A4%A7%E8%BF%9E%E7%90%86%E5%B7%A5%E5%A4%A7%E5%AD%A6%E5%9F%8E%E5%B8%82%E5%AD%A6%E9%99%A2&ie=utf-8&pn=0");

// Only receive content of files with content-type "text/html"
$crawler->addContentTypeReceiveRule("#text/html#");
//获取话题
$crawler->addURLFollowRule(
    "#http://tieba\.baidu\.com/f\?kw=.*?&ie=utf-8&pn=\d+?#i");
//获取话题页面
$crawler->addURLFollowRule(
    "#http://tieba.baidu\.com/p/\d+?#i");

// Ignore links to pictures, dont even request pictures
$crawler->addURLFilterRule("#\.(jpg|jpeg|gif|png)$# i");

// Store and send cookie-data like a browser does
$crawler->enableCookieHandling(true);

// Set the traffic-limit to 1 MB (in bytes,
// for testing we dont want to "suck" the whole site)
$crawler->setTrafficLimit(0);

// Thats enough, now here we go
$crawler->go();

// At the end, after the process is finished, we print a short
// report (see method getProcessReport() for more information)
$report = $crawler->getProcessReport();

if (PHP_SAPI == "cli") $lb = "\n";
else $lb = "<br />";

echo "Summary:".$lb;
echo "Links followed: ".$report->links_followed.$lb;
echo "Documents received: ".$report->files_received.$lb;
echo "Bytes received: ".$report->bytes_received." bytes".$lb;
echo "Process runtime: ".$report->process_runtime." sec".$lb;
?>