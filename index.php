<!DOCTYPE html>
<html lang="ru">
    <head>
        <title>Файлообменник</title>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="/src/bootstrap4/css/bootstrap.min.css">
        <link rel="stylesheet" href="css/bootstrap-toggle.min.css">
        <link rel="stylesheet" href="css/upload_btn.css">
        <link rel="stylesheet" href="css/default.css">

        <script src="/src/js/jquery-3.4.1.min.js"></script>
        <script src="/src/bootstrap4/js/bootstrap.min.js"></script>       
        <script src="js/bootstrap-toggle.min.js"></script>
    </head>
    <body>
        <? include('../menu.php'); ?>
        <div class="container">
            <!-- Image and text -->
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Файлообменник</h5>
                            <p class="alert alert-light">Все файлы хранятся месяц</p>
                            <form action="" enctype="multipart/form-data" method="POST" role="form">
                                <div class="row justify-content-center">
                                    <div class="col-auto">
                                        <div class="form-group">
                                            <div class="button">
                                                <input id="file" type="file" multiple="multiple" name="file[]"/>
                                            </div>
                                        </div>
    
                                    </div>
                                </div>
                                <div class="row justify-content-center">
                                    <div class="col-auto">
                                        <div class="form-group ">
                                            <label>
                                                <input type="checkbox" name="arh-checkbox" id="arh-checkbox" data-toggle="toggle" checked>
                                                Архивировать
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <div class="progress progress-striped active" style="height: 20px;">
                                                <div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%;">
                                                    <div class="sr-onlys" style="color: white; display: block;">0%</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div id="info" class="alert"></div><!-- сюда будет выводится информация о загрузке -->  
                            </form>
                            <p class="text-center text-muted">Максимальное кол-во загружаемых файлов не должно превышать 600 шт. и в сумме не более 5Гб. Ссылка на загрузку файлов доступна только внутри корпоративной сети ВОЭ.</p>
                            <p class="text-center text-muted">
                                <small>доступно места на сервере: <?php 
                                         echo formatSizeUnits(disk_free_space("/var/www")); 
                                ?></small>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php

        function formatSizeUnits($bytes)
        {
            if ($bytes >= 1073741824)
            {
                $bytes = number_format($bytes / 1073741824, 2) . ' Гб';
            }
            elseif ($bytes >= 1048576)
            {
                $bytes = number_format($bytes / 1048576, 2) . ' Мб';
            }
            elseif ($bytes >= 1024)
            {
                $bytes = number_format($bytes / 1024, 2) . ' Кб';
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
        ?>

        <script>
            $(document).ready(function () {
                var $checked = true;

                $('#arh-checkbox').on('change', function (e) {
                    // console.log(e.currentTarget.checked);
                    $checked = e.currentTarget.checked;
                });



                function progressHandlingFunction(e) {
                    if (e.lengthComputable) {
                        $('.progress').show();
                        $('.progress-bar').css('width', e.loaded / e.total * 100 + '%');
                        $('.sr-onlys').text(Math.round(e.loaded / e.total * 100) + '%');
                        if (e.loaded / e.total * 100 == 100) {
                            var i = 0;
                            setInterval(function () {
                                i++;
                                $('.sr-onlys').text('Подождите, идет архивирование... (прошло сек: ' + i + ')');
                            }, 1000);
                        }
                    }
                }

                $('.progress').hide();

                $('#file').bind('change', function () {

                    var data = new FormData();
                    var error = '';
                    data.append('checked', $checked);
                    // console.log(data);

                    jQuery.each($('#file')[0].files, function (i, file) {

                        if (file.name.length < 1) {
                            error = error + ' Файл имеет неправильный размер! ';
                        }
                        if (file.size > 5048000000000) {
                            error = error + ' ' + file.name + ' <br> <strong>Файл имеет слишком большой размер</strong>';
                        }
                        /*
                         if(file.type != 'image/png' && file.type != 'image/jpg' && !file.type != 'image/gif' && file.type != 'image/jpeg' ) {
                         error = error + 'File  ' + file.name + '  doesnt match png, jpg or gif';
                         }*/

                        data.append('file-' + i, file);

                    });

                    if (error != '') {
                        $('#info').html(error).addClass('alert-danger');
                    } else {

                        $.ajax({
                            url: 'upload.php',
                            type: 'POST',
                            xhr: function () {
                                var myXhr = $.ajaxSettings.xhr();
                                if (myXhr.upload) { // проверка что осуществляется upload
                                    myXhr.upload.addEventListener('progress', progressHandlingFunction, false); //передача в функцию значений
                                }
                                return myXhr;
                            },
                            data: data,
                            cache: false,
                            contentType: false,
                            processData: false,
                            beforeSend: function () {
                                $('.progress').show();
                            },
                            success: function (data) {
                                $('#info').html(data);
                                $('.form-group').hide();

                            }

                            ,
                            error: errorHandler = function () {
                                $('#info').html('Ошибка загрузки файлов. Скорее всего вы отправили слишком большой объем файлов (больше 5Гб)').addClass('alert-danger');
                            }

                        });

                    }
                })

            });


        </script>

    </body>
</html>
