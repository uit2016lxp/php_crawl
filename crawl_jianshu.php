<?php
/**
 * Created by PhpStorm.
 * User: lxp1055
 * Date: 2018/4/27
 * Time: 23:01
 */
require "simple_html_dom.php";
class crawl_jianshu
{
    function request_jianshu($url){//请求页面内容
        $curlHandler = curl_init($url);
        curl_setopt($curlHandler, CURLOPT_SSL_VERIFYPEER, FALSE);//跳过安全检查
        curl_setopt($curlHandler, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curlHandler, CURLOPT_URL, $url);
        curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, 1);

        $agent = 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.146 Safari/537.36';
        curl_setopt($curlHandler, CURLOPT_USERAGENT, $agent);

        $result = curl_exec($curlHandler);
        curl_close($curlHandler);
       return $result;
    }
    function extractSHD_user($content)//匹配出用户数据（包括用户名和用户网址
    {//使用simple_html_dom.php爬取主要数据
        $shd = new simple_html_dom();
        $shd->load($content);
        $users_name=array();
        $users_url=array();
        $users=array();
        $users_un=array();
        $n=-1;
        foreach ($shd->find("div.info a") as $e) {
            $maches=array();
            $pant='/<a class="nickname" target="_blank" href="\/u\/(.*?)">(.*?)<\/a>/';
            preg_match($pant,$e,$maches);
            if($maches)
                $users_name[++$n]=$maches[2];
            if($maches)
              $users_url[$n]="https://www.jianshu.com/u/".$maches[1];
            $users[$users_name[$n]]=$users_url[$n];
        }
        $users_un=array_unique($users);
        return $users_un;
    }
    function extractSHD_article($content){//匹配出文章数据（包括文章网址，标题和简介)
        $shd = new simple_html_dom();
        $shd->load($content);
        $article_url=array();
        $article_title=array();
        $article_abstract=array();
        $article=array();
        $n=-1;
        $m=0;
        foreach ($shd->find("a.title") as $e) {
           $maches=$this->regular_mate_data_data($e,'/<a class="title" target="_blank" href="\/p\/(.*?)">(.*?)<\/a>/');
           $article_url[++$n]="https://www.jianshu.com/p/".$maches[1];
           $article_title[$n]=$maches[2];
        }
        foreach ($shd->find("p.abstract")as $e){
            $maches=$this->regular_mate_data_data($e,'/<p class="abstract">(.*?)<\/p>/');
            $article_abstract[$m++]=$maches[1];
        }
        $article[0]=$article_url;
        $article[1]=$article_title;
        $article[2]=$article_abstract;
        return $article;
    }
    function regular_mate_data_data($content, $pant)
    {//使用正则表达式精准匹配
        $maches = array();
        preg_match($pant, $content, $maches);
        return $maches;
    }
    function analysis_article_user($users_un){//分析每个作者每个文章的网址所在页面找出文章底下推荐阅读的作者，分析每个作者的信息，文章等；
        $user_article=array();
        $n=-1;
        foreach ($users_un as $k=>$v){
           $content=$this->request_jianshu($v);
           $article=$this->extractSHD_article($content);
           $user_article[++$n][0]=$k;
           $user_article[$n][1]=$v;
           $user_article[$n][2]=$article[0];
           $user_article[$n][3]=$article[1];
           $user_article[$n][4]=$article[2];
        }
        return $user_article;
    }
    function analysis_user($article_urls){//分析每个作者文章的网址，爬取作者信息和文章信息；
        $user_article=array();
        $n=-1;
        foreach ($article_urls as $v){
            $content=$this->request_jianshu($v);
            $users=$this->extractSHD_article_users($content);
          foreach ($users as $k=>$v) {
              $content = $this->request_jianshu($v);
              $article = $this->extractSHD_article($content);
              $user_article[++$n][0] = $k;
              $user_article[$n][1] = $v;
              $user_article[$n][2] = $article[0];
              $user_article[$n][3] = $article[1];
              $user_article[$n][4] = $article[2];

          }
        }
        $this->save_to_mysql($user_article);
    }
    function extractSHD_article_users($content){//分析出作者的用户名和网址；
        $shd = new simple_html_dom();
        $shd->load($content);
        $user_urls=array();//保存匹配到的用户的url;
        $user_name=array();//保存匹配到的用户的name;
        $users=array();
        $n=-1;
        $m=-1;
        foreach ($shd->find("a.author") as $e){
           $user_urls[++$n]="https://www.jianshu.com/".$e->href."\n";
        }
        foreach ($shd->find("span.name")as $e) {
            $maches = $this->regular_mate_data_data($e, '/(?<=>)(.*?)(?=<\/)/');//保存每次匹配到的用户名字
            $user_name[++$m]=$maches[1];
        }
        //return $users;
        for($i=0;$i<$m;$i++)
            $users[$user_name[$i+1]]=$user_urls[$i];
        return $users;
    }
    function save_to_mysql($user_article){//将匹配到的数据保存到数据库中
        $servername = 'localhost';
        $username = 'root';
        $password = 'root';
        try {
            $conn = new PDO("mysql:host=$servername;dbname=jianshu", $username, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => "set names utf8"));
            $conn->setAttribute(PDO::ATTR_ERRMODE, pdo::ERRMODE_EXCEPTION);
            //开始事物
            $conn->beginTransaction();
            foreach ($user_article as $k => $v) {//author_info表保存作者名和网址
                $sql = "INSERT INTO author_info (author,author_url) values (:author,:author_url)";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':author', $user_article[$k][0]);
                $stmt->bindParam(':author_url', $user_article[$k][1]);
                $stmt->execute();
                foreach ($user_article[$k][2] as $key=>$val) {//article_info表保存作者名，该作者每篇文章的标题，简介和文章的网址
                    $sql = "INSERT INTO article_info (author,title, introduction,article_url) values (:author,:title,:introduction,:article_url)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bindParam(':author', $user_article[$k][0]);
                    $stmt->bindParam(':title', $user_article[$k][3][$key]);
                    $stmt->bindParam(':introduction', $user_article[$k][4][$key]);
                    $stmt->bindParam(':article_url', $user_article[$k][2][$key]);
                    $stmt->execute();
                }
              }
            $conn->commit();
        }catch(PDOException $e){
            //如果执行失败回滚
            $conn->rollBack();
            echo "Error".$e->getMessage();
        }
        $conn=null;
        $stmt=null;
        echo "content has by save "."\n";
    }
}
$url = "https://www.jianshu.com/";//初始网址
$Crawl = new crawl_jianshu();//新建一个对象
$content = $Crawl->request_jianshu($url);//请求网页内容
$users_un = $Crawl->extractSHD_user($content);//得到匹配出用户数据（包括用户名和用户网址）
$user_article=$Crawl->analysis_article_user($users_un);//分析每个作者每个文章的网址所在页面找出文章底下推荐阅读的作者，分析每个作者的信息，文章等；
foreach ($user_article as $v) {
  $Crawl->analysis_user($v[2]);//分析每个作者文章的网址爬取作者信息和文章信息；
}
?>
//大概用户名等信息爬取了300多条，文章等信息爬取了2000多条。

