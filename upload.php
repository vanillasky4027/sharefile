<?php

include_once('./lib/pclzip/pclzip.lib.php'); //Подключаем библиотеку.
// функция превода текста с кириллицы в траскрипт
// без учета регистра только для файлов

function TranslateFileName($filename)
{
    $tr = array(
        "А" => "a", "Б" => "b", "В" => "v", "Г" => "g",
        "Д" => "d", "Е" => "e", "Ё" => "e", "Ж" => "j", "З" => "z", "И" => "i",
        "Й" => "y", "К" => "k", "Л" => "l", "М" => "m", "Н" => "n",
        "О" => "o", "П" => "p", "Р" => "r", "С" => "s", "Т" => "t",
        "У" => "u", "Ф" => "f", "Х" => "h", "Ц" => "ts", "Ч" => "ch",
        "Ш" => "sh", "Щ" => "sch", "Ь" => "", "Ъ" => "", "Ы" => "yi", "Ь" => "",
        "Э" => "e", "Ю" => "yu", "Я" => "ya", "а" => "a", "б" => "b",
        "в" => "v", "г" => "g", "д" => "d", "е" => "e", "ё" => "e", "ж" => "j",
        "з" => "z", "и" => "i", "й" => "y", "к" => "k", "л" => "l",
        "м" => "m", "н" => "n", "о" => "o", "п" => "p", "р" => "r",
        "с" => "s", "т" => "t", "у" => "u", "ф" => "f", "х" => "h",
        "ц" => "ts", "ч" => "ch", "ш" => "sh", "щ" => "sch", "ъ" => "y",
        "ы" => "yi", "ь" => "", "ъ" => "", "э" => "e", "ю" => "yu", "я" => "ya",
        " " => "_", "," => "", "." => ".", "/" => "_", "№" => "Number"
    );
    return strtr($filename, $tr);
}

function show_dir($dir)
{ // функция показа картинок из tmp папки
    $list = scandir($dir);
    unset($list[0], $list[1]);
    foreach ($list as $file)
    {
        echo "<div class=\"alert alert-success\"><a href=\"$dir" . $file . "\">$file</a></div><br>";
    }
}

function delTree($dir)
{
    $files = array_diff(scandir($dir), array('.', '..'));
    foreach ($files as $file)
    {
        (is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file");
    }
    return rmdir($dir);
}

function delFiles($dir)
{
    /*
     * Удаляем файлы старше 30 дней и пустые папки
     */
    $files = array_diff(scandir($dir), array('.', '..'));
    foreach ($files as $file)
    {
        if (is_dir("$dir/$file"))
        {
            delFiles("$dir/$file");
        }
        else
        {
            $date1 = new DateTime("now");
            $date2 = new DateTime(date("d.m.Y", filectime("$dir/$file")));
            $diff = $date1->diff($date2);
            if ($diff->days > 30)
            {
                if ($file !== '.htaccess')
                {
                    unlink("$dir/$file");
                }
            }
        }
    }
    @rmdir($dir);
}

function loging($href)
{
    $hostname = gethostbyaddr($_SERVER['REMOTE_ADDR']);
    $file = './sharefile/logs/log' . date('Y_m_j') . '.txt';
    $fileSize = formatSizeUnits(@filesize(str_ireplace("http://" . $_SERVER['HTTP_HOST'], ".", $href)));

    $data = $hostname . " | " . $_SERVER['REMOTE_ADDR'] . " | " . date('Y.m.j H:i:s') . "| " . $href . " | " . $fileSize . ";  \r\n";
    // Пишем содержимое в файл,
    // используя флаг FILE_APPEND flag для дописывания содержимого в конец файла
    // и флаг LOCK_EX для предотвращения записи данного файла кем-нибудь другим в данное время
    file_put_contents($file, $data, FILE_APPEND | LOCK_EX);
}

function formatSizeUnits($bytes)
{
    if ($bytes >= 1073741824)
    {
        $bytes = number_format($bytes / 1073741824, 2) . ' GB';
    }
    elseif ($bytes >= 1048576)
    {
        $bytes = number_format($bytes / 1048576, 2) . ' MB';
    }
    elseif ($bytes >= 1024)
    {
        $bytes = number_format($bytes / 1024, 2) . ' KB';
    }
    elseif ($bytes > 1)
    {
        $bytes = $bytes . ' bytes';
    }
    elseif ($bytes == 1)
    {
        $bytes = $bytes . ' byte';
    }
    else
    {
        $bytes = '0 bytes';
    }

    return $bytes;
}

/* ------------------------------------------------------------------------------------------- */
$count = 0;
$cryptfolder = "_" . md5(date('j_m_Y_H_i_s') . "voefiles");
mkdir("./sharefile/tmp/" . $cryptfolder, 0700);
mkdir("./sharefile/download/" . $cryptfolder, 0700);

//var_dump($_POST['checked']);
foreach ($_FILES as $key => $value)
{ //перемещение файлов в tmp
    move_uploaded_file($value['tmp_name'], "./sharefile/tmp/" . $cryptfolder . "/" . TranslateFileName($value['name']));
    $count++;
}

//echo($count);
if ($count > 1 or $count == 1 && $_POST['checked'] == 'true')
{
    //echo 'arh';
    // Делаем архив
    $dw_file = './sharefile/download/' . date('Ymj_His') . $cryptfolder . '.zip';
    $archive = new PclZip($dw_file); //Создаём объект и в качестве аргумента, указываем название архива, с которым работаем.
    $result = $archive->create('./sharefile/tmp/' . $cryptfolder . '/', PCLZIP_OPT_REMOVE_PATH, './sharefile/tmp/' . $cryptfolder . '/');
    //// Этим методом класса мы создаём архив с заданным выше названием 
    // Если всё прошло хорошо, возращаем массив с данными (время создание архива, занесённым файлом и т.д)
}
else
{
    /*
     * Если файл один то перемещаем его в отдельную папку и кидаем ссылку пользователю
     */
    rename(
            "./sharefile/tmp/" . $cryptfolder . "/" . TranslateFileName($_FILES['file-0']['name']), "./sharefile/download/" . $cryptfolder . "/" . TranslateFileName($_FILES['file-0']['name'])
    );
    $dw_file = "./sharefile/download/" . $cryptfolder . "/" . TranslateFileName($_FILES['file-0']['name']);
    $result = 1;
}


//var_dump($result); 
if ($result == 0)
{
    echo $archive->errorInfo(true); //Возращает причину ошибки
}

@delTree('./sharefile/tmp/' . $cryptfolder);
delFiles('./sharefile/download/');

//показываем файл
echo '
<div class="alert alert-success text-center">
  <h3><a href="' . $dw_file . '" class="btn btn-primary btn-lg btn-block">Скачать файл</a></h3>
      <h3><a href="mailto:?subject=Файлообменник ВОЭ&body=Чтобы скачать файл нажмите на ссылку: http://' . $_SERVER['HTTP_HOST'] . ltrim($dw_file, ".") . '  '
 . ' Размер файла: ' . formatSizeUnits(@filesize($dw_file)) . '" class="btn btn-primary btn-lg btn-block">Отправить ссылку по почте</a></h3>
      <p>Размер файла: ' . formatSizeUnits(@filesize($dw_file)) . '</p>
  <hr>
  <div class="form">
    <label for="link">Ссылка для загрузки отправленных вами файлов</label>
    <textarea class="form-control" id="link" value=""  rows="3">http://' . $_SERVER['HTTP_HOST'] . ltrim($dw_file, ".") . '</textarea>
    <br><a href="/" class="btn btn-success btn-lg btn-block">Загрузить новые файлы</a>
  </div>
</div>';
//  пишем лог
loging('http://' . $_SERVER['HTTP_HOST'] . ltrim($dw_file, "."));
