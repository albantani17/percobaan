<?php 
// data yang dibutuhkan untuk Koneksi olt
$host = ["10.0.160.46","10.0.160.42","10.0.160.38","10.0.160.34","10.0.160.62"];
// session unregister Onu
function session_olt($host, $community) {
    $session = new SNMP(SNMP::VERSION_2C, $host, $community);
    $oid_unreg = 'iso.3.6.1.4.1.3902.1082.500.10.2.2.5.1.2';
    $oid_type = 'iso.3.6.1.4.1.3902.1082.500.10.2.2.5.1.7';

    $walk_unreg = @$session->walk($oid_unreg);
    $walk_type = @$session->walk($oid_type);

    if (!$walk_unreg || !$walk_type) {
        return []; // Kembalikan array kosong jika ada kegagalan
    }
    
    // Inisialisasi array untuk menyimpan hasil
    $results = array();

    $hostname = change_ip($host);

    for ($i = 0; $i < count($walk_unreg); $i++) {
        $key_unreg = array_keys($walk_unreg)[$i];
        $key_type = array_keys($walk_type)[$i];
        $gpon = get_gpon($key_unreg);
        $sn = get_sn($walk_unreg, $key_unreg);
        $type = get_type($walk_type, $key_type);

        // Hanya tambahkan ke $results jika data tidak kosong
        if (!empty($gpon) && !empty($sn) && !empty($type)) {
            $results[] = [
                'host' => $hostname,
                'gpon' => $gpon,
                'sn' => $sn,
                'type' => $type,
            ];
        }
    }

    // Mengembalikan array hasil untuk digunakan di luar fungsi
    return $results;
}

function change_ip($host) {
    if ($host == "10.0.160.46"){
        return $host = "Jiput";
    } elseif ($host == "10.0.160.42") {
        return $host = "Mandalawangi";
    } elseif ($host == "10.0.160.38") {
        return $host = "Majasari";
    } elseif ($host == "10.0.160.34") {
        return $host = "Saketi";
    } elseif ($host == "10.0.160.62") {
        return $host = "Padarincang";
    }
}

function get_gpon($key_unreg) {
    $gpon = substr($key_unreg, 41, 9);
    $gpon_map = array(
        285278465 => "1/1/1", 285278466 => "1/1/2", 285278467 => "1/1/3", 
        285278468 => "1/1/4", 285278469 => "1/1/5", 285278470 => "1/1/6", 
        285278471 => "1/1/7", 285278472 => "1/1/8", 285278473 => "1/1/9", 
        285278474 => "1/1/10", 285278475 => "1/1/11", 285278476 => "1/1/12", 
        285278477 => "1/1/13", 285278478 => "1/1/14", 285278479 => "1/1/15", 
        285278480 => "1/1/16", 285278721 => "1/2/1", 285278722 => "1/2/2", 
        285278723 => "1/2/3", 285278724 => "1/2/4", 285278725 => "1/2/5", 
        285278726 => "1/2/6", 285278727 => "1/2/7", 285278728 => "1/2/8", 
        285278729 => "1/2/9", 285278730 => "1/2/10", 285278731 => "1/2/11", 
        285278732 => "1/2/12", 285278733 => "1/2/13", 285278734 => "1/2/14", 
        285278735 => "1/2/15", 285278736 => "1/2/16"
    );
    
    return isset($gpon_map[$gpon]) ? $gpon_map[$gpon] : null;
}

function get_sn($walk_unreg, $key_unreg) {
    $sbr1 = substr($walk_unreg[$key_unreg], 11);
    $sbr2 = str_replace(' ', '', $sbr1);
    $sbr3 = substr($sbr2, 0, 8);
    $sbr4 = substr($sbr2, 8);

    return hexToStr($sbr3) . $sbr4;
}

function get_type($walk_type, $key_type) {
    $type1 = substr($walk_type[$key_type], 9);
    $type2 = substr($type1, 0, -1);
    return $type2;
}

function hexToStr($hex) {
    $string = '';
    for ($i = 0; $i < strlen($hex) - 1; $i += 2) {
        $string .= chr(hexdec($hex[$i] . $hex[$i + 1]));
    }
    return $string;
}
// END OF UNREGISTER ONU
?>