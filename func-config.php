<?php 
$server = ["10.0.160.38","10.0.160.42","10.0.160.34","10.0.160.46","10.0.160.62"];
$port = 22;
$username = ["zte","alban"];
$password = ["zte","123456"];
$methods = array(
    'kex' => 'diffie-hellman-group1-sha1',
    'client_to_server' => array(
      'crypt' => 'aes128-cbc',
      'comp' => 'none'),
    'server_to_client' => array(
      'crypt' => 'aes128-cbc',
      'comp' => 'none'));

$olt = $_POST["olt"];
$gpon = $_POST["gpon"];
$onuid = $_POST["id"];
$nama = $_POST["nama"];
$type = $_POST["type"];
$sn = $_POST["sn"];
$vlan = $_POST["vlan"];
$pppoe = $_POST["pppoe"];
$profile = $_POST["profile"];
$ssh = ssh2_connect(req_olt($olt), $port, $methods);

if (isset($_POST["home_submit"])) { 
    if (ssh2_auth_password($ssh, check_user($olt), check_pw($olt))) {
        echo "Berhasil Tersambung\n";
        check_mode($ssh, "home", $gpon, $onuid, $nama, $type, $sn, $vlan, $pppoe, $profile);
    } else {
        die('Gagal Terhubung');
    }
} elseif (isset($_POST["hotspot_submit"]) ) {
    if (ssh2_auth_password($ssh, check_user($olt), check_pw($olt))) {
        echo "Berhasil Tersambung\n";
        check_mode($ssh, "hotspot", $gpon, $onuid, $nama, $type, $sn, $vlan, $pppoe, $profile);
    } else {
        die('Gagal Terhubung');
    }
} 





function req_olt($olt){
    global $server;
    $olt_list = ["Majasari", "Mandalawangi", "Saketi", "Jiput", "Padarincang"];
    return $server[array_search($olt, $olt_list)];
}

function check_user($olt) {
    global $username;
    if (in_array($olt, ["majasari", "Mandalawangi", "Jiput", "Padarincang"])) {
        return $username[0];
    } elseif ($olt == "Saketi") {
        return $username[1];
    }
}

function check_pw($olt) {
    global $password;
    if (in_array($olt, ["Majasari", "Mandalawangi", "Jiput", "Padarincang"])) {
        return $password[0];
    } elseif ($olt == "Saketi") {
        return $password[1];
    }
}

function check_mode($ssh, $mode, $gpon, $onuid, $nama, $type, $sn, $vlan, $pppoe, $profile) {
    if ($mode == "home") {
        home_config($ssh, $gpon, $onuid, $nama, $type, $sn, $vlan, $pppoe, $profile);
    } elseif ($mode == "hotspot") {
        hotspot_config($ssh, $slot, $gpon, $onuid, $nama, $type, $sn, $vlan);
    }
}

function home_config($ssh, $gpon, $onuid, $nama, $type, $sn, $vlan, $pppoe, $profile){
    $s = ssh2_shell($ssh, 'vt102', null, 80, 24, SSH2_TERM_UNIT_CHARS);
    stream_set_blocking($s, true);
    
    $commands = [
        "conf t",
        "interface gpon-olt_$gpon",
        "onu $onuid type $type sn $sn",
        "exit",
        "interface gpon-onu_$gpon:$onuid",
        "name $nama",
        "sn bind enable",
        "tcont 1 name 1 profile UP-1G",
        "gemport 1 name 1 tcont 1 queue 1",
        "gemport 1 traffic-limit upstream DOWN-1G downstream DOWN-1G",
        "service-port 1 vport 1 user-vlan $vlan vlan $vlan",
        "exit",
        "pon-onu-mng gpon-onu_$gpon:$onuid",
        "service ServiceName gemport 1 vlan $vlan",
        "security-mgmt 1 state enable mode forward protocol web",
        "wan-ip 1 mode pppoe username $pppoe password $pppoe vlan-profile $profile host 1",
        "wan-ip 1 ping-response enable traceroute-response enable",
        "exit",
        "write"
    ];

    foreach ($commands as $cmd) {
        fwrite($s, $cmd .PHP_EOL);
        usleep(500000); // Tunggu setengah detik agar perintah diproses
    }

    fclose($s);
    echo "berhasil terkirim";
    header("Location: index.php");
}

function hotspot_config($ssh, $gpon, $onuid, $nama, $type, $sn, $vlan) {
    $s = ssh2_shell($ssh, 'vt102', null, 80, 24, SSH2_TERM_UNIT_CHARS);
    stream_set_blocking($s, true);
    
    $commands = [
        "conf t",
        "interface gpon-olt_$gpon",
        "onu $onuid type $type sn $sn",
        "exit",
        "interface gpon-onu_$gpon:$onuid",
        "name $nama",
        "sn bind enable",
        "tcont 1 name 1 profile UP-1G",
        "gemport 1 name 1 tcont 1 queue 1",
        "gemport 1 traffic-limit upstream DOWN-1G downstream DOWN-1G",
        "service-port 1 vport 1 user-vlan $vlan vlan $vlan",
        "exit",
        "pon-onu-mng gpon-onu_$gpon:$onuid",
        "service ServiceName gemport 1 vlan $vlan",
        "vlan port wifi_0/1 mode tag vlan $vlan",
        "vlan port wifi_0/2 mode tag vlan $vlan",
        "vlan port wifi_0/3 mode tag vlan $vlan",
        "vlan port wifi_0/4 mode tag vlan $vlan",
        "vlan port eth_0/1 mode tag vlan $vlan",
        "vlan port eth_0/2 mode tag vlan $vlan",
        "vlan port eth_0/3 mode tag vlan $vlan",
        "vlan port eth_0/4 mode tag vlan $vlan",
        "dhcp-ip ethuni eth_0/1 from-internet $vlan",
        "dhcp-ip ethuni eth_0/2 from-internet $vlan",
        "dhcp-ip ethuni eth_0/3 from-internet $vlan",
        "dhcp-ip ethuni eth_0/4 from-internet $vlan",
        "exit",
        "write"
    ];

    foreach ($commands as $cmd) {
        fwrite($s, $cmd.PHP_EOL);
        usleep(500000);
    }

    fclose($s);
    echo "berhasil terkirim";
    header("Location: index.php");
}
?>