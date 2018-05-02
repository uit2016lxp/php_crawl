<?php
require "city_analog_landing.php";

function regular_mate_data($content, $pant)
{//使用正则表达式精准匹配
    $maches = array();
    preg_match_all($pant, $content, $maches);
    return $maches;
}

$student_id='你的学号';
$password='你的密码';
$city_anlog=new city_analog_landing();
$city_anlog->getcookie();//获得cookie
$city_anlog->getverify();//获得验证码
$city_anlog->login($student_id,$password);//登陆
$content=$city_anlog->getdata();//获取内容
//echo $content;//输出返回内容
$curriculum=array();//课程
$teacher=array();//教师
$place=array();//上课地点
$frequency=array();//节数
$user=array();//保存登陆人的课程信息
$pant = '/(?<=>)(.*?)<br style="mso-data-placement:same-cell">(.*?)<br style="mso-data-placement:same-cell">(.*?)<br style="mso-data-placement:same-cell">(.*?)(?=<)/';
//$pant='/(?<=>)(.*?)<br style="mso-data-placement:same-cell">/';
$maches=regular_mate_data($content, $pant);

$curriculum=$maches[1];
$teacher=$maches[2];
$place=$maches[3];
$frequency=$maches[4];
$n=1;
foreach ($curriculum as $k=>$v) {
    $user[$n++] = $curriculum[$k] . ' ' . $teacher[$k] . ' ' . $place[$k] . ' ' . $frequency[$k] . "\n";
}
if(!empty($user))
  print_r($user);//输出课程
else
    echo "请求失败!";//请求失败代表模拟登陆失败
?>

