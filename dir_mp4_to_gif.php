<?php
// How do I convert a video to GIF using ffmpeg, with reasonable quality?
// https://superuser.com/questions/556029/how-do-i-convert-a-video-to-gif-using-ffmpeg-with-reasonable-quality
$dir_input  = 'z:/upl1';
$dir_output = 'z:/upl1/thumb';
$dir_tmp    = 'z:/vid/tmp';
$fname_file_list = __DIR__ . '/mp4list.txt';
$time_start = 30; // Seconds
$time_gif = 3; // Seconds
$time_segment = 2; // Seconds
$cnt_segment = 10; // Number of segments
$is_save_gif = true;
$is_save_mp4 = true;
// Adding leading zeros
function add_zero($num,$kolvo) {
    $num=abs($num);
    $len=strlen($num);
    if ($len<$kolvo) return trim(str_repeat('0',$kolvo-$len).$num);
    else return trim($num);
}

// Читаем каталог в массив только один уровень
// $is_add_dir - добавлять в название полный путь к файлу или каталогу
// $is_file - добавлять только файлы, иначе добавлять только каталоги
function dir_to_array_nr($dir,$is_add_dir=false,$is_file=true) {
    $r=array();
    if (!is_dir($dir)) return false;
    $cdir = scandir($dir);
    foreach ($cdir as $key => $value)
    {
        if (!in_array($value,array('.','..')))
        {
            if ($is_file)
            {
                if (is_file($dir . '/' . $value))
                {
                    if ($is_add_dir) $r[] = $dir . '/' . $value;
                    else $r[] = $value;
                }
            }
            else
            {
                if (is_dir($dir . '/' . $value))
                {
                    if ($is_add_dir) $r[] = $dir . '/' . $value;
                    else $r[] = $value;
                }
            }
        }
    }
    return $r;
}

// Deleting all files in a directory
function delete_all_files_in_dir($dirname) {
  $ar_file=dir_to_array_nr($dirname);
  if ($ar_file!=false) {
    foreach($ar_file as $fname) 
      unlink($dirname . DIRECTORY_SEPARATOR . $fname);
  }
}

function make_dir_if_not_exists($dirname) {
  if (is_dir($dirname)) return true;
  else
  {
    if (mkdir($dirname)==false) return false;
    else return true;
  }
}


if (make_dir_if_not_exists($dir_output)==false) {
    exit("Error create dir $dir_input " . PHP_EOL);
}

if (make_dir_if_not_exists($dir_tmp)==false) {
    exit("Error create dir $dir_tmp " . PHP_EOL);
}
    
$fname = '';
$ar_fname = dir_to_array_nr($dir_input,true);
if ($ar_fname == false) {
    exit("Error read dir $dir_input " . PHP_EOL);
}

$cmd_segment = 'ffmpeg -i "{FILEMP4}" -c copy -map 0:v -segment_time {TIMESEGMENT} -g 5 -f segment -reset_timestamps 1 ' . $dir_tmp . '/output%05d.mp4';

//$cmd_template = 'ffmpeg -ss {TIMESTART} -t {TIMEGIF} -i "{FILEMP4}" -vf "fps=10,scale=320:-1:flags=lanczos,split[s0][s1];[s0]palettegen[p];[s1][p]paletteuse" -loop 0 "{FILEGIF}"';

if ($is_save_gif) {
    $cmd_template_gif = 'ffmpeg -y -f concat -safe 0 -i "'.$fname_file_list.'" -vf "fps=5,scale=320:-1:flags=lanczos,split[s0][s1];[s0]palettegen[p];[s1][p]paletteuse" -loop 0 "{FILEGIF}"';
}
if ($is_save_mp4) {
    $cmd_template_mp4 = 'ffmpeg -y -f concat -safe 0 -i "'.$fname_file_list.'" -vf "fps=30,scale=480:-1:flags=lanczos" "{FILEGIF}"';
    
}

foreach($ar_fname as $fname) {
    if ((strpos(strtolower($fname),'.mp4')===false) && (strpos(strtolower($fname),'.wmv')===false)) {
        continue;
    }
    delete_all_files_in_dir($dir_tmp);
    $bname = basename($fname); // Basename MP4
    $filegif = '';$filemp4 = '';
    if ($is_save_gif) {
        $bname_gif = str_replace(['.mp4','.wmv'],'',$bname).'.gif'; 
        $filegif = $dir_output . DIRECTORY_SEPARATOR . $bname_gif; 
    }
    if ($is_save_mp4) {
        $bname_mp4 = 'tn_'.$bname; 
        $filemp4 = $dir_output . DIRECTORY_SEPARATOR . $bname_mp4; 
    }
    echo "$fname GIF:$filegif MP4:$filemp4" . PHP_EOL;
    // Делим на сегменты видео
    $cmd = str_replace(['{FILEMP4}','{TIMESEGMENT}'],[$fname,$time_segment],$cmd_segment);
    echo "$cmd" . PHP_EOL;
    exec($cmd);
    $ar_file_segment = dir_to_array_nr($dir_tmp,true);
    if ($ar_file_segment == false) {
        echo "Not found files $dir_tmp" . PHP_EOL;
        exit;
        continue;
    }
    
    $cnt_files = count($ar_file_segment);
    $max_number_of_file = round($cnt_files / $cnt_segment);
    $cnt_file_segment = 0;
    $str_save = '';
    foreach($ar_file_segment as $fname_segment) {
        $is_delete_file = true;
        if ($cnt_file_segment >= $max_number_of_file) {
            $cnt_file_segment = 0;
            $is_delete_file = false;
        }
        $cnt_file_segment ++;
        if ($is_delete_file) {
            unlink($fname_segment);
        } else {
            $str_save .= "file '$fname_segment'" . PHP_EOL;
        }
    }
    file_put_contents($fname_file_list,$str_save);
    if ($is_save_gif) {
        $cmd = str_replace(['{TIMESTART}','{TIMEGIF}','{FILEMP4}','{FILEGIF}'],[$time_start,$time_gif,$fname,$filegif],$cmd_template_gif);
        echo "$cmd" . PHP_EOL;
        exec($cmd);
    }
    if ($is_save_mp4) {
        $cmd = str_replace(['{TIMESTART}','{TIMEGIF}','{FILEMP4}','{FILEGIF}'],[$time_start,$time_gif,$fname,$filemp4],$cmd_template_mp4);
        echo "$cmd" . PHP_EOL;
        exec($cmd);
    }
    delete_all_files_in_dir($dir_tmp);
    
}
        
