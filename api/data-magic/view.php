<?php 
    include '../../controllers/data-magic.php';
    $datamagic = new DataMagic(1);
    return $datamagic->magicView(null, null);
 