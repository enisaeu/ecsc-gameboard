<?php
    require_once("common.php");
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <title><?php echo TITLE; ?></title>
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta http-equiv="Content-Type" content="text/html;charset=utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1,shrink-to-fit=no"/>
        <meta name="description" content="<?php echo TITLE; ?> - European Cyber Security Challenge">
        <meta name="keywords" content="CTF,Capture the Flag,Challenge,Competition,Puzzles,Security,Cybersecurity,ENISA,EU">
        <link rel="shortcut icon" href="<?php echo joinPaths(PATHDIR, '/favicon.ico');?>">
        <link rel="preload" href="<?php echo joinPaths(PATHDIR, '/resources/agency-fb.woff');?>" as="font" crossorigin="anonymous"/>
        <link rel="preload" href="<?php echo joinPaths(PATHDIR, '/resources/logo.jpg');?>" as="image" crossorigin="anonymous"/>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha256-YLGeXaapI0/5IgZopewRJcFXomhRMlYYjugPLSyNjTY=" crossorigin="anonymous"/>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.19/css/jquery.dataTables.min.css" integrity="sha256-YY1izqyhIj4W3iyJOaGWOpXDSwrHWFL4Nfk+W0LyCHE=" crossorigin="anonymous" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.9.0/css/all.min.css" integrity="sha256-UzFD2WYH2U1dQpKDjjZK72VtPeWP50NoJjd26rnAdUI=" crossorigin="anonymous"/>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/3.3.0/css/flag-icon.min.css" integrity="sha256-NkXMfPcpoih3/xWDcrJcAX78pHpfwxkhNj0bAf8AMTs=" crossorigin="anonymous"/>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-datetimepicker/2.5.20/jquery.datetimepicker.min.css" integrity="sha256-DOS9W6NR+NFe1fUhEE0PGKY/fubbUCnOfTje2JMDw3Y=" crossorigin="anonymous"/>
        <link rel="stylesheet" href="<?php echo joinPaths(PATHDIR, '/resources/main.css');?>">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js" integrity="sha256-KM512VNnjElC30ehFwehXjx1YCHPiQkOPmqnrWtpccM=" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/canvasjs/1.7.0/canvasjs.min.js" integrity="sha256-CIc5A981wu9+q+hmFYYySmOvsA3IsoX+apaYlL0j6fg=" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.15.0/umd/popper.min.js" integrity="sha256-fTuUgtT7O2rqoImwjrhDgbXTKUwyxxujIMRIK7TbuNU=" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha256-CjSoeELFOcH0/uxWu6mC/Vlrc1AARqbm/jiiImDGV3s=" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.19/js/jquery.dataTables.min.js" integrity="sha256-t5ZQTZsbQi8NxszC10CseKjJ5QeMw5NINtOXQrESGSU=" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-datetimepicker/2.5.20/jquery.datetimepicker.full.min.js" integrity="sha256-FEqEelWI3WouFOo2VWP/uJfs1y8KJ++FLh2Lbqc8SJk=" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-sparklines/2.1.2/jquery.sparkline.min.js" integrity="sha256-BuAkLaFyq4WYXbN3TFSsG1M5GltEeFehAMURi4KBpUM=" crossorigin="anonymous"></script>
        <script src="<?php echo joinPaths(PATHDIR, '/resources/jquery.sortElements.js');?>"></script>
        <script src="<?php echo joinPaths(PATHDIR, '/resources/main.js?v2');?>"></script>
        <noscript>
            <style>
                #main_container { display: none; }
                noscript {
                    position: absolute;
                    margin: auto;
                    top: 50%;
                    width: 100%;
                    text-align: center;
                    transform: perspective(1px) translateY(-50%);
                    color: red;
                }
            </style>
        </noscript>
