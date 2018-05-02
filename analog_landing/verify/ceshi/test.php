<?php

include ('Valite.php');

$valite = new Valite();
$valite->setImage('C:\Users\lxp1055\PhpstormProjects\untitled1\NetworkWorm\analog_landing\verify\verifyCode.png');
$valite->getHec();
$valite->Draw();
$ert=$valite->run();
echo $ert;

?>