<?php
require 'verify\phpVerify\verify.php';//调用百度OCR接口

class city_analog_landing
{
    protected $cookie_file;
    function __construct()
    {
        $this->cookie_file = dirname(__FILE__) . "/pic.cookie";    //cookie文件存放位置（自定义）给出一个包含有指向一个文件的全路径的字符串，本函数返回去掉文件名后的目录名。
    }
    function getcookie()
    {
        $login_url = 'http://cityjw.dlut.edu.cn:7001/';   //登录页面网址


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $login_url);//需要获取的 URL 地址，也可以在curl_init() 初始化会话的时候。
        curl_setopt($ch, CURLOPT_HEADER, 0);// 启用时会将头文件的信息作为数据流输出。
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//TRUE 将curl_exec()获取的信息以字符串返回，而不是直接输出。
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie_file);//连接结束后，比如，调用 curl_close 后，保存 cookie 信息的文件。
        curl_exec($ch);//执行 cURL 会话
        curl_close($ch);//结束会话
    }

    function getverify()
    {
        $verify_url = "http://cityjw.dlut.edu.cn:7001/ACTIONVALIDATERANDOMPICTURE.APPPROCESS";     //获取验证码网址

        $ch = curl_init();//初始化 cURL 会话
        curl_setopt($ch, CURLOPT_URL, $verify_url);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_file);//包含 cookie 数据的文件名，cookie 文件的格式可以是 Netscape 格式，或者只是纯 HTTP 头部风格，存入文件。如果文件名是空的，不会加载 cookie，但 cookie 的处理仍旧启用。
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $verify_img = curl_exec($ch);
        curl_close($ch);

        $fp = fopen("./verifyCode.png", 'w');   //把抓取到的图片文件写入本地图片文件保存
        fwrite($fp, $verify_img);
        fclose($fp);
    }

    function login($student_id,$password)
    {
        $verify = VerifyCode();//获得验证码的值
        $post_url = 'http://cityjw.dlut.edu.cn:7001/ACTIONLOGON.APPPROCESS?mode=4';     //登录表单提交网址
        $post = "WebUserNO=$student_id&Password=$password&Agnomen=$verify";//表单提交的数据（根据表单字段名和用户输入决定）
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $post_url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);         //全部数据使用HTTP协议中的 "POST" 操作来发送
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_file);
        $con = curl_exec($ch);
        curl_close($ch);
    }

    function getdata()
    {
        $data_url = "http://cityjw.dlut.edu.cn:7001/ACTIONQUERYSTUDENTSCHEDULEBYSELF.APPPROCESS";     //获取数据所在网址
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $data_url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);// 允许 cURL 函数执行的最长秒数。
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_file);
        $data = curl_exec($ch);
        curl_close($ch);
        return iconv('gb2312', 'UTF-8', $data);//将字符串 $data 从 gb2312转换编码到 utf-8。
    }
}
?>
