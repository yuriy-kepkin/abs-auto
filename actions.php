<?php

require 'config.php';
require 'Upload.php';

$upload = new Upload();

// В зависимости от запроса выбираем экшн
switch ($_POST['app_req']) {
    case 'upload': {
        if ($_FILES['file']['tmp_name']) {
            $upl = move_uploaded_file($_FILES['file']['tmp_name'], 'uploads/' . $_FILES['file']['name']);

            $added_data = $upload->addFileData($_FILES['file']['name']);

            echo json_encode($added_data);
        } else {
            echo false;
        }

        break;
    }
    case 'lasthours': {
        $hours = $_POST['hours'];

        $result = $upload->lastHours($hours);

        echo json_encode($result);

        break;
    }
    case 'midprice': {
        $result = $upload->midPrice();

        echo json_encode($result);

        break;
    }
    case 'interval': {
        $date_from = $_POST['date_from'];
        $date_to = $_POST['date_to'];

        $result = $upload->interval($date_from, $date_to);

        echo json_encode($result);

        break;
    }
    default: {
        // no such action
    }
}