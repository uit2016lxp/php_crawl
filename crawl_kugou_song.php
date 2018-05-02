<?php

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
        echo "Page requested: " . $DocInfo->url . " (" . $DocInfo->http_status_code . ")" . $lb;

        // Print the refering URL
        // echo "Referer-page: ".$DocInfo->referer_url.$lb;

        // Print if the content of the document was be recieved or not
        if ($DocInfo->received == true)
            echo "Content received: " . $DocInfo->bytes_received . " bytes" . $lb;
        else
            echo "Content not received" . $lb;

        // Now you should do something with the content of the actual
        // received page or file ($DocInfo->source), we skip it in this example
        $url = $DocInfo->url;
        $pat = "/http:\/\/www\.kugou.com\/yy\/special\/single\/\d+\.html/";
        if (preg_match($pat, $url) > 0) {
            $this->parseSonglistDetail($DocInfo);
        }
        flush();
    }

    public function parseSonglistDetail($DocInfo)
    {
        $songlistArr = array();
        $songlistArr['raw_url'] = $DocInfo->url;
        $content = $DocInfo->content;
        //名称
        $pat = "/<span>名称：<\/span>([^(<br)]+)<br \/>/";
        $maches = array();
        if (preg_match($pat, $content, $maches) > 0)
            $songlistArr['title'] = $maches[1];
        else
            $songlistArr['title'] = "null";
        //创建人
        $pat = "/<span>创建人：<\/span>([^(<br)]+)<br \/>/";
        $maches = array();
        if (preg_match($pat, $content, $maches) > 0)
            $songlistArr['founder'] = $maches[1];
        else
            $songlistArr['founder'] = "null";
        //更新时间
        $pat = "/<span>更新时间：<\/span>(.*)<br \/>/";
        $maches = array();
        if (preg_match($pat, $content, $maches) > 0)
            $songlistArr['uptime'] = $maches[1];
        else
            $songlistArr['uptime'] = "null";
        //简介
        $pat = '/<div class="intro"><p><span>简介：<\/span>(.*)<\/p><\/div>/';
        $maches = array();
        if (preg_match($pat, $content, $maches) > 0)
            $songlistArr['brife'] = $maches[1];
        else
            $songlistArr['brife'] = "null";
        //找歌曲名

        $songlistArr['song'] = $this->extractSHD($content);
        $this->save_song($songlistArr);
        //print_r($songlistArr);
    }

    function extractSHD($content)
    {//使用simple_html_dom.php爬取主要数据
        $val = array();
        $shd = new simple_html_dom();
        $shd->load($content);
        foreach ($shd->find(/*"ul.listMusic li div.name a"*//*"ul.listbgdot li div.text a"*//*"div.book_top_page ol.bgGray li a"*/
            "div.r div.list1 ul li a") as $e) {
            $n = $this->regular_mate_data_data($e->title, "/.*(?=-)/");
            $songva = $this->regular_mate_data_data($e->title, "/(?<=- ).*/");
            $val[$n] = $songva;
        }
        return $val;
    }

    function regular_mate_data_data($content, $pant)
    {//使用正则表达式匹配演出演唱者
        $maches = array();
        preg_match($pant, $content, $maches);
        return $maches[0];
    }

    function save_song($songlistArr)
    {
        $servername = 'localhost';
        $username = 'root';
        $password = 'LxpMysql.12';
        try{
            $conn = new PDO("mysql:host=$servername;dbname=song", $username, $password,array(PDO::MYSQL_ATTR_INIT_COMMAND => "set names utf8"));
            $conn->setAttribute(PDO::ATTR_ERRMODE,pdo::ERRMODE_EXCEPTION);
            //开始事物
            $conn->beginTransaction();
            $songinfolist=array();
            $songinfolist['title']=$songlistArr['title'];
            $songinfolist['founder']=$songlistArr['founder'];
            $songinfolist['uptime']=$songlistArr['uptime'];
            $songinfolist['brife']=$songlistArr['brife'];
            $sql="INSERT INTO songinfo (title,founder,uptime,brife) value (:title,:founder,:uptime,:brife)";
            $stmt=$conn->prepare($sql);
            $stmt->bindParam(':title',$songinfolist['title']);
            $stmt->bindParam(':founder',$songinfolist['founder']);
            $stmt->bindParam(':uptime',$songinfolist['uptime']);
            $stmt->bindParam(':brife',$songinfolist['brife']);
            $stmt->execute();
            $founder=$songinfolist['founder'];
            foreach ($songlistArr['song'] as $k=>$v){
                $sql="INSERT INTO songlist (founder,singer,song) value (:founder,:singer,:song)";
                $stmt=$conn->prepare($sql);
                $stmt->bindParam(':founder',$founder);
                $stmt->bindParam(':singer',$k);
                $stmt->bindParam(':song',$v);
                $stmt->execute();
            }
            $conn->commit();
        }catch(PDOException $e){
            // 如果执行失败回滚
            $conn->rollBack();
            echo "Error".$e->getMessage();
        }
        $conn=null;
        $stmt=null;
    }
}

// Now, create a instance of your class, define the behaviour
// of the crawler (see class-reference for more options and details)
// and start the crawling-process.

$crawler = new MyCrawler();

// URL to crawl
$start_url="http://www.kugou.com/yy/special/index/1-0-2.html";
$crawler->setURL($start_url);

// Only receive content of files with content-type "text/html"

$crawler->addContentTypeReceiveRule("#text/html#");
//每一个精选辑的链接
$crawler->addURLFollowRule(
        "#http://www\.kugou\.com/yy/special/single/\d+\.html# i");
//精选辑页面的链接
$crawler->addURLFollowRule(
    "#http://www\.kugou\.com/yy/special/index/\d-0-2\.html# i");
// Ignore links to pictures, dont even request pictures
$crawler->addURLFilterRule("#\.(jpg|jpeg|gif|png)$# i");

// Store and send cookie-data like a browser does
$crawler->enableCookieHandling(true);

// Set the traffic-limit to 1 MB (in bytes,
// for testing we dont want to "suck" the whole site)
//0表示无限
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