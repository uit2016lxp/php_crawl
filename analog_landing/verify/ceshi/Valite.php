<?php

define('WORD_WIDTH',9);
define('WORD_HIGHT',13);
define('OFFSET_X',7);
define('OFFSET_Y',3);
define('WORD_SPACING',4);

class valite
{
    public function setImage($Image)
    {
        $this->ImagePath = $Image;
    }
    public function getHec()
    {
        $res = imagecreatefromjpeg($this->ImagePath);
        $size = getimagesize($this->ImagePath);
        $data = array();
        for($i=0; $i < $size[1]; ++$i)
        {
            for($j=0; $j < $size[0]; ++$j)
            {
                $rgb = imagecolorat($res,$j,$i);
                $rgbarray = imagecolorsforindex($res, $rgb);
                if($rgbarray['red'] < 125 || $rgbarray['green']<125
                    || $rgbarray['blue'] < 125)
                {
                    $data[$i][$j]='*';
                }else{
                    $data[$i][$j]=0;
                }
            }
        }
        $this->DataArray = $data;
        $this->ImageSize = $size;
    }
    public function run()
    {
        $result="";
        // ????4??????
        $data = array("","","","");
        for($i=0;$i<4;++$i)
        {
            $x = ($i*(WORD_WIDTH+WORD_SPACING))+OFFSET_X;
            $y = OFFSET_Y;
            for($h = $y; $h < (OFFSET_Y+WORD_HIGHT); ++ $h)
            {
                for($w = $x; $w < ($x+WORD_WIDTH); ++$w)
                {
                    $data[$i].=$this->DataArray[$h][$w];
                }
            }

        }

        // ???§Û???????
        foreach($data as $numKey => $numString)
        {
            $max=0.0;
            $num = 0;
            foreach($this->Keys as $key => $value)
            {
                $percent=0.0;
                similar_text($value, $numString,$percent);
                if(intval($percent) > $max)
                {
                    $max = $percent;
                    $num = $key;
                    if(intval($percent) > 95)
                        break;
                }
            }
            $result.=$num;
        }
        $this->data = $result;
        // ??????????????
        return $result;
    }

    public function Draw()
    {
        for($i=0; $i<$this->ImageSize[1]; ++$i)
        {
            for($j=0; $j<$this->ImageSize[0]; ++$j)
            {
                echo $this->DataArray[$i][$j];
            }
            echo "\n";
        }
    }
    public function __construct()
    {
        $this->Keys = array(
            '0' => '000**00000****000**00**0**0000****0000****0000****0000**0**00**000****00000**000',
            '1' => '00**000***00****0000**0000**0000**0000**0000**0000**00******',
            '2' => '00****000**00**0**0000**000000**00000**00000**00000**00000**00000**00000********',
            '3' => '0*****00**000**0000000**00000**0000***0000000**0000000**000000****000**00*****00',
            '4' => '00000**00000***0000****000**0**00**00**0**000**0********00000**000000**000000**0',
            '5' => '*******0**000000**000000**0***00***00**0000000**000000****0000**0**00**000****00',
            '6' => '00****000**00**0**0000*0**000000**0***00***00**0**0000****0000**0**00**000****00',
            '7' => '********000000**000000**00000**00000**00000**00000**00000**00000**000000**000000',
            '8' => '00****000**00**0**0000**0**00**000****000**00**0**0000****0000**0**00**000****00',
            '9' => '00****000**00**0**0000****0000**0**00***00***0**000000**0*0000**0**00**000****00',
        );
    }
    protected $ImagePath;
    protected $DataArray;
    protected $ImageSize;
    protected $data;
    protected $Keys;
    protected $NumStringArray;

}
?>