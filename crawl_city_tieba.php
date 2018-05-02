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

        //Print the URL and the HTTP-status-Code;
       // echo "Page requested: ".$DocInfo->url." (".$DocInfo->http_status_code.")".$lb;

        // Print the refering URL
         //echo "Referer-page: ".$DocInfo->referer_url.$lb;

        // Print if the content of the document was be recieved or not
        if ($DocInfo->received == true){}
           // echo "Content received: " . $DocInfo->bytes_received . " bytes" . $lb;
        else{}
           // echo "Content not received" . $lb;

        // Now you should do something with the content of the actual
        // received page or file ($DocInfo->source), we skip it in this example

        $pant='/http:\/\/tieba\.baidu\.com\/f\?kw=(.*?)&ie=utf-8&pn=\d+/';
        if(preg_match($pant,$DocInfo->url)>0){
            $tieba=array();
            $tieba=$this->regular_mate_data($DocInfo->content);
            $this->crawl_tieba($tieba);
        }
        flush();
    }
    function  crawl_tieba($tieba){
        $tieba_aou=array();
        $tieba_ti=array();
        $tieba_top=array();
        $n=0;
        foreach ($tieba[3] as $v){
            $tieba_aou[$n++]=$v;
        }
        $n=0;
        foreach ($tieba[2] as $v){
            $tieba_ti[$n++]=$v;
        }
        $n=0;
        foreach ($tieba_ti as $v){
            $tieba_top[$tieba_aou[$n++]]=$v;
        }
        print_r($tieba_top);
        //$this->save_content($tieba_top);//保存到数据库;
    }
    function save_content($tieba_top)
    {
        $servername = 'localhost';
        $username = 'root';
        $password = 'root';
        try{
            $conn = new PDO("mysql:host=$servername;dbname=tieba", $username, $password,array(PDO::MYSQL_ATTR_INIT_COMMAND => "set names utf8"));
            $conn->setAttribute(PDO::ATTR_ERRMODE,pdo::ERRMODE_EXCEPTION);
            //开始事物
            $conn->beginTransaction();
            foreach ($tieba_top as $k=>$v) {
                $sql="INSERT INTO tieba_content (author,title) values (:author,:title)";
                $stmt=$conn->prepare($sql);
                $stmt->bindParam(':author', $k);
                $stmt->bindParam(':title', $v);
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
    function regular_mate_data($content)
    {//使用正则表达式匹配
        $maches=array();
        $pant='/ <a rel="noreferrer"  href="\/p\/\d+" title=(.*?) target="_blank" class="j_th_tit ">(.*?)<\/a>
<\/div><div class="threadlist_author pull_right">
    <span class="tb_icon_author "
          title=(.*)
/';
        preg_match_all($pant,$content,$maches);
      return $maches;
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
    "#http://tieba\.baidu\.com/f\?kw=.*&ie=utf-8&pn=\d+#i");
//获取话题页面
/*$crawler->addURLFollowRule(
    "#http://tieba.baidu\.com/p/\d+#i");*/

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
