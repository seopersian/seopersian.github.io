<?php
$file = 'https://strapi.zabanshenas.com/uploads/learn_at_home_40c0f965ff.mp4';
$newfile = 'video.mp4';

if (!copy($file, $newfile)) {
echo "copy nashod $file…n";
}else{
echo "انتقال فایل انجام شد";
}
?>