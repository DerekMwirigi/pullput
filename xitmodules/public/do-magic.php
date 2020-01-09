<?php 
    include '../src/magic/magic.php';
    $magic = new Magic();
    //php://input
    echo json_encode($magic->doMagic(json_decode(file_get_contents('../src/models/app.json'), true)));