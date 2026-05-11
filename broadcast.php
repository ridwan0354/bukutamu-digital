<?php
require 'koneksi.php';
require_login();
check_csrf();

// Cek & Buat Tabel broadcast_queue jika belum ada
mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS `broadcast_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` int(11) DEFAULT 0,
  `nama_tamu` varchar(255) DEFAULT NULL,
  `nomor_wa` varchar(20) DEFAULT NULL,
  `pesan` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `link_undangan` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Cek & Update Database (Otomatis Tambah Kolom jika belum ada)
$check_cols = mysqli_query($koneksi, "SHOW COLUMNS FROM broadcast_queue LIKE 'event_id'");
if($check_cols && mysqli_num_rows($check_cols) == 0){ mysqli_query($koneksi, "ALTER TABLE broadcast_queue ADD COLUMN event_id INT DEFAULT 0"); }

$check_cols = mysqli_query($koneksi, "SHOW COLUMNS FROM tamu LIKE 'event_id'");
if($check_cols && mysqli_num_rows($check_cols) == 0){ mysqli_query($koneksi, "ALTER TABLE tamu ADD COLUMN event_id INT DEFAULT 0"); }

$check_cols = mysqli_query($koneksi, "SHOW COLUMNS FROM broadcast_queue LIKE 'link_undangan'");
if($check_cols && mysqli_num_rows($check_cols) == 0){ mysqli_query($koneksi, "ALTER TABLE broadcast_queue ADD COLUMN link_undangan TEXT"); }

$check_cols = mysqli_query($koneksi, "SHOW COLUMNS FROM pengaturan LIKE 'tutorial_link'");
if($check_cols && mysqli_num_rows($check_cols) == 0){ mysqli_query($koneksi, "ALTER TABLE pengaturan ADD COLUMN tutorial_link TEXT"); }

$check_cols = mysqli_query($koneksi, "SHOW COLUMNS FROM pengaturan LIKE 'broadcast_img'");
if($check_cols && mysqli_num_rows($check_cols) == 0){ mysqli_query($koneksi, "ALTER TABLE pengaturan ADD COLUMN broadcast_img TEXT"); }

$check_cols = mysqli_query($koneksi, "SHOW COLUMNS FROM events LIKE 'broadcast_img'");
if($check_cols && mysqli_num_rows($check_cols) == 0){ mysqli_query($koneksi, "ALTER TABLE events ADD COLUMN broadcast_img TEXT"); }

$check_cols = mysqli_query($koneksi, "SHOW COLUMNS FROM events LIKE 'broadcast_link'");
if($check_cols && mysqli_num_rows($check_cols) == 0){ mysqli_query($koneksi, "ALTER TABLE events ADD COLUMN broadcast_link TEXT"); }

$check_cols = mysqli_query($koneksi, "SHOW COLUMNS FROM events LIKE 'broadcast_param_id'");
if($check_cols && mysqli_num_rows($check_cols) == 0){ mysqli_query($koneksi, "ALTER TABLE events ADD COLUMN broadcast_param_id INT DEFAULT 1"); }

$check_cols = mysqli_query($koneksi, "SHOW COLUMNS FROM events LIKE 'wa_token'");
if($check_cols && mysqli_num_rows($check_cols) == 0){ mysqli_query($koneksi, "ALTER TABLE events ADD COLUMN wa_token VARCHAR(255) DEFAULT NULL"); }

$check_cols = mysqli_query($koneksi, "SHOW COLUMNS FROM events LIKE 'wa_template'");
if($check_cols && mysqli_num_rows($check_cols) == 0){ mysqli_query($koneksi, "ALTER TABLE events ADD COLUMN wa_template TEXT DEFAULT NULL"); }

$check_cols = mysqli_query($koneksi, "SHOW COLUMNS FROM events LIKE 'tutorial_link'");
if($check_cols && mysqli_num_rows($check_cols) == 0){ mysqli_query($koneksi, "ALTER TABLE events ADD COLUMN tutorial_link TEXT"); }

// 1. AJAX HANDLER: LOAD QUEUE TABLE ONLY
if(isset($_GET['ajax_load_queue'])){
    $evt_id = (int)$_GET['event_id'];
    $loading_queue = mysqli_query($koneksi,"SELECT * FROM broadcast_queue WHERE event_id='$evt_id' ORDER BY id ASC");
    if(mysqli_num_rows($loading_queue)>0){
        while($row=mysqli_fetch_assoc($loading_queue)){
            $initial=strtoupper(mb_substr($row['nama_tamu'],0,1));
            $status_html = "";
            if($row['status']=='sent') $status_html = '<span class="inline-flex items-center gap-1 text-green-600 font-bold text-[10px] bg-green-50 px-2 py-0.5 rounded-md border border-green-100 uppercase"><iconify-icon icon="solar:check-circle-bold" width="10"></iconify-icon> <span class="hidden md:inline">SENT</span></span>';
            elseif($row['status']=='failed') $status_html = '<span class="inline-flex items-center gap-1 text-red-600 font-bold text-[10px] bg-red-50 px-2 py-0.5 rounded-md border border-red-100 uppercase">FAIL</span>';
            else $status_html = '<span class="inline-flex items-center gap-1 text-[#a1887f] font-bold text-[10px] bg-[#ffffff] px-2 py-0.5 rounded-md border border-[#e8e1d5] uppercase">PENDING</span>';

            echo '<tr class="group hover:bg-[#fffbf2] transition queue-row flex flex-row items-center justify-between p-3 md:table-row md:p-0 border-b border-[#ffffff] md:border-none" id="row-'.$row['id'].'" data-id="'.$row['id'].'" data-status="'.$row['status'].'">';
            echo '<td class="flex-1 md:table-cell md:px-4 md:py-2.5 md:border-b border-[#e8e1d5] pr-2 min-w-0 align-middle"><div class="flex items-center gap-3"><div class="w-8 h-8 rounded-full bg-[#faf7f0] border border-[#e8e1d5] text-[#87714c] flex items-center justify-center text-xs font-bold shrink-0 shadow-sm">'.$initial.'</div><div class="flex flex-col min-w-0"><div class="font-bold text-[#000000] text-sm truncate">'.htmlspecialchars($row['nama_tamu']).'</div><div class="text-[11px] text-[#a1887f] font-mono">'.htmlspecialchars($row['nomor_wa']).'</div><div class="md:hidden text-[10px] text-gray-400 truncate mt-0.5">'.htmlspecialchars(substr(str_replace(["\r\n","\r","\n"],' ',$row['pesan']),0,30)).'...</div></div></div></td>';
            echo '<td class="hidden md:table-cell md:px-4 md:py-2.5 md:border-b border-[#e8e1d5] align-middle"><div class="text-[11px] text-gray-500 truncate max-w-[180px] bg-[#ffffff] border border-[#e8e1d5] px-2 py-1 rounded-md">'.htmlspecialchars(substr(str_replace(["\r\n","\r","\n"],' ',$row['pesan']),0,50)).'...</div></td>';
            echo '<td class="shrink-0 md:table-cell md:px-4 md:py-2.5 text-center md:border-b border-[#e8e1d5] align-middle" id="status-col-'.$row['id'].'">'.$status_html.'</td>';
            echo '<td class="shrink-0 md:table-cell md:px-4 md:py-2.5 text-center md:border-b border-[#e8e1d5] pl-2 align-middle"><div class="flex items-center justify-center gap-2"><button type="button" onclick="actionQueue(\'reset\','.$row['id'].')" class="text-[#a1887f] hover:text-blue-600 hover:bg-blue-50 transition p-1.5 rounded-lg flex justify-center" title="Reset Status"><iconify-icon icon="solar:restart-bold-duotone" width="16"></iconify-icon></button><button type="button" onclick="actionQueue(\'hapus\','.$row['id'].')" class="text-[#a1887f] hover:text-red-500 hover:bg-red-50 transition p-1.5 rounded-lg flex justify-center" title="Hapus"><iconify-icon icon="solar:trash-bin-minimalistic-bold-duotone" width="16"></iconify-icon></button></div></td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="4" class="p-8 text-center text-gray-400 italic text-xs">Belum ada antrian data untuk event ini.</td></tr>';
    }
    exit;
}

// 2. AJAX HANDLER: SEND MESSAGE (FOR INDIVIDUAL BROADCAST)
if(isset($_POST['ajax_send_id'])){
    header('Content-Type: application/json');
    $qid=(int)$_POST['ajax_send_id'];
    $evt_id=isset($_POST['current_event_id'])?(int)$_POST['current_event_id']:0;
    
    // Ambil data antrian (Pastikan status pending)
    $q_data=mysqli_query($koneksi,"SELECT * FROM broadcast_queue WHERE id='$qid' AND status='pending' LIMIT 1");
    if(mysqli_num_rows($q_data)==0){echo json_encode(['status'=>'error','msg'=>'Data tidak ditemukan/sudah terkirim']);exit;}
    $row=mysqli_fetch_assoc($q_data);
    
    // Tentukan Token & Config berdasarkan Event ID
    $token='';$img_url='';
    if($evt_id>0){
        $cek_evt=mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT wa_token, broadcast_img FROM events WHERE id='$evt_id'"));
        $token=$cek_evt['wa_token']??'';$img_url=$cek_evt['broadcast_img']??'';
    }
    // Fallback ke Global Config jika token event kosong
    if(empty($token)){
        $cek_glob=mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT wa_token, broadcast_img FROM pengaturan WHERE id=1"));
        $token=$cek_glob['wa_token']??'';
        if(empty($img_url))$img_url=$cek_glob['broadcast_img']??'';
    }
    
    if(empty($token)){echo json_encode(['status'=>'error','msg'=>'Token WA belum diisi']);exit;}
    
    // Prepare Fonnte Request
    $clean_msg = str_replace(['\r\n','\r','\n'],"\n",$row['pesan']);
    $postData=['target'=>$row['nomor_wa'],'message'=>$clean_msg];
    if(!empty($img_url))$postData['url']=$img_url;
    
    // Check if there is a link to send as a button
    if(!empty($row['link_undangan'])){
        $postData['buttons'] = "Buka Undangan|".$row['link_undangan'];
    }
    
    $curl=curl_init();
    curl_setopt_array($curl,[CURLOPT_URL=>'https://api.fonnte.com/send',CURLOPT_RETURNTRANSFER=>true,CURLOPT_ENCODING=>'',CURLOPT_MAXREDIRS=>10,CURLOPT_TIMEOUT=>30,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_HTTP_VERSION=>CURL_HTTP_VERSION_1_1,CURLOPT_CUSTOMREQUEST=>'POST',CURLOPT_POSTFIELDS=>$postData,CURLOPT_HTTPHEADER=>["Authorization: $token"]]);
    $response=curl_exec($curl);$err=curl_error($curl);curl_close($curl);
    
    if($err){
        mysqli_query($koneksi,"UPDATE broadcast_queue SET status='failed' WHERE id='$qid'");
        echo json_encode(['status'=>'error','msg'=>'cURL Error: '.$err]);
    }else{
        $resp_arr=json_decode($response,true);
        if($resp_arr&&isset($resp_arr['status'])&&$resp_arr['status']==true){
            mysqli_query($koneksi,"UPDATE broadcast_queue SET status='sent' WHERE id='$qid'");
            echo json_encode(['status'=>'success','msg'=>'Terkirim']);
        }else{
            $fail_reason=$resp_arr['reason']??($resp_arr['detail']??'Gagal Gateway');
            mysqli_query($koneksi,"UPDATE broadcast_queue SET status='failed' WHERE id='$qid'");
            echo json_encode(['status'=>'error','msg'=>$fail_reason]);
        }
    }
    exit;
}

// 3. AJAX HANDLER: RESET FAILED STATUS
if(isset($_POST['ajax_reset_failed'])){
    header('Content-Type: application/json');
    $evt_id = (int)$_POST['event_id'];
    $q = mysqli_query($koneksi,"UPDATE broadcast_queue SET status='pending' WHERE status='failed' AND event_id='$evt_id'");
    if($q) echo json_encode(['status'=>'success']);
    else echo json_encode(['status'=>'error', 'msg'=>mysqli_error($koneksi)]);
    exit;
}

// SECURITY CHECK
if(!isset($_SESSION['status'])||$_SESSION['status']!="login"){header("Location: login");exit;}
$uid=$_SESSION['user_id'];
$role=$_SESSION['role'];
$parent_id=$_SESSION['parent_id'] ?? 0;
// Receptionist acts on behalf of parent
$effective_uid = ($role == 'receptionist' && $parent_id > 0) ? $parent_id : $uid;
$swal_script="";

// SETUP EVENT ID & LIST
$q_events_list=($role=='admin')?mysqli_query($koneksi,"SELECT id, event_name FROM events ORDER BY id DESC"):mysqli_query($koneksi,"SELECT id, event_name FROM events WHERE user_id='$effective_uid' ORDER BY id DESC");
$selected_event_id=isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;

// Auto select event if needed
if(empty($selected_event_id) && mysqli_num_rows($q_events_list)>0){
    mysqli_data_seek($q_events_list,0);
    $first=mysqli_fetch_assoc($q_events_list);
    $selected_event_id=(int)$first['id'];
    mysqli_data_seek($q_events_list,0);
}

// SECURITY: Verify Event Ownership (Prevent IDOR)
if(!empty($selected_event_id) && $role != 'admin'){
    $check_owner = mysqli_query($koneksi, "SELECT id FROM events WHERE id='$selected_event_id' AND user_id='$effective_uid'");
    if(mysqli_num_rows($check_owner) == 0){
        die("<div style='font-family:sans-serif;padding:50px;text-align:center;'>
            <h2 style='color:#e53e3e'>Akses Ditolak!</h2>
            <p>Anda tidak memiliki izin untuk mengelola event ini.</p>
            <a href='broadcast.php' style='color:#87714c;text-decoration:none;font-weight:bold;'>[ Kembali ke Broadcast Saya ]</a>
        </div>");
    }
}

// HANDLE MASTER PARAM (ADMIN ONLY)
if($role=='admin'){
    if(isset($_POST['add_param'])){
        $p=esc(str_replace(['?','='],'',$_POST['new_param']));
        if(!empty($p)){
            $cek=mysqli_query($koneksi,"SELECT id FROM master_broadcast_params WHERE param_key='$p'");
            if(mysqli_num_rows($cek)==0){mysqli_query($koneksi,"INSERT INTO master_broadcast_params (param_key) VALUES ('$p')");$swal_script="Toast.fire({icon: 'success', title: 'Parameter ditambahkan'});";}
            else{$swal_script="Toast.fire({icon: 'error', title: 'Parameter sudah ada'});";}
        }
    }
    if(isset($_POST['del_param'])){
        $id_p=(int)$_POST['id_param'];
        mysqli_query($koneksi,"DELETE FROM master_broadcast_params WHERE id=$id_p");
        header("Location: broadcast.php?msg=param_del&event_id=$selected_event_id");
        exit;
    }
}

if(isset($_GET['msg'])&&$_GET['msg']=='param_del')$swal_script="Toast.fire({icon: 'success', title: 'Parameter dihapus'});";

// LOAD CONFIGURATION
$config_global=mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT * FROM pengaturan LIMIT 1"));
$evt_data=null;$is_specific=false;
$val_link=$config_global['broadcast_link']??'';$val_img=$config_global['broadcast_img']??'';
$val_token=$config_global['wa_token']??'';$val_tmpl=$config_global['wa_template']??"";
$val_pid=$config_global['broadcast_param_id']??1;
$val_tut=(!empty($config_global['tutorial_link'])) ? $config_global['tutorial_link'] : 'https://www.youtube.com/watch?v=6PstbC6hQig';

function getYoutubeEmbedUrl($url){
    if(empty($url)) return null;
    $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/|youtube\.com\/shorts\/|youtube\.com\/live\/)([^"&?\/\s]{11})/i';
    if(preg_match($pattern, $url, $matches)){
        return "https://www.youtube.com/embed/" . $matches[1];
    }
    return null;
}

if(!empty($selected_event_id)){
    $q_evt=mysqli_query($koneksi,"SELECT * FROM events WHERE id='$selected_event_id'");
    if(mysqli_num_rows($q_evt)>0){
        $evt_data=mysqli_fetch_assoc($q_evt);$is_specific=true;
        if(!empty($evt_data['broadcast_link']))$val_link=$evt_data['broadcast_link'];
        if(!empty($evt_data['broadcast_img']))$val_img=$evt_data['broadcast_img'];
        if(!empty($evt_data['wa_template']))$val_tmpl=$evt_data['wa_template'];
        if(!empty($evt_data['wa_token']))$val_token=$evt_data['wa_token'];
        if(!empty($evt_data['broadcast_param_id']))$val_pid=$evt_data['broadcast_param_id'];
        if(!empty($evt_data['tutorial_link']))$val_tut=$evt_data['tutorial_link'];
    }
}
$embed_url=getYoutubeEmbedUrl($val_tut);$show_gen=$config_global['show_generate']??1;

// SAVE CONFIGURATION
if(isset($_POST['save_config'])){
    $link=mysqli_real_escape_string($koneksi,$_POST['broadcast_link']);$img=mysqli_real_escape_string($koneksi,$_POST['broadcast_img']);
    $token=mysqli_real_escape_string($koneksi,$_POST['wa_token']);
    $tmpl=mysqli_real_escape_string($koneksi,$_POST['pesan_template']);
    $pid=(int)$_POST['param_id'];$tut=isset($_POST['tutorial_link'])?mysqli_real_escape_string($koneksi,$_POST['tutorial_link']):'';
    if($is_specific&&!empty($selected_event_id)){
        mysqli_query($koneksi,"UPDATE events SET broadcast_link='$link', broadcast_img='$img', wa_token='$token', wa_template='$tmpl', broadcast_param_id='$pid', tutorial_link='$tut' WHERE id='$selected_event_id'");
        $swal_script="Toast.fire({icon: 'success', title: 'Pengaturan EVENT disimpan'});";
    }elseif($role=='admin'){
        mysqli_query($koneksi,"UPDATE pengaturan SET broadcast_link='$link', broadcast_img='$img', wa_token='$token', wa_template='$tmpl', broadcast_param_id='$pid', tutorial_link='$tut' WHERE id=1");
        $swal_script="Toast.fire({icon: 'success', title: 'Pengaturan GLOBAL disimpan'});";
    }
    $val_link=$link;$val_img=$img;$val_tmpl=$tmpl;$val_pid=$pid;$val_token=$token;
    if($role=='admin'){$val_tut=$tut;$embed_url=getYoutubeEmbedUrl($val_tut);}
}

// GENERATE DATA (Scoped by Event ID)
if(isset($_POST['generate_data'])){
    $raw_data=$_POST['input_data'];$process_generate=true;
    if(!empty($_FILES['csv_file']['name'])){
        $filename=$_FILES['csv_file']['name'];$ext=strtolower(pathinfo($filename,PATHINFO_EXTENSION));
        if($ext!=='csv'){$swal_script="ModalAlert.fire('File Tidak Didukung', 'Mohon upload file dengan format .CSV saja.', 'error');";$process_generate=false;}
        else{$file=fopen($_FILES['csv_file']['tmp_name'],'r');while(($row=fgetcsv($file))!==FALSE){if(isset($row[0]))$raw_data.="\n".$row[0]."|".($row[1]??'');}fclose($file);}
    }
    if($process_generate){
        $pid_used=isset($_POST['param_id'])?(int)$_POST['param_id']:$val_pid;
        $d_p=mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT param_key FROM master_broadcast_params WHERE id='$pid_used'"));
        $param_key=$d_p['param_key']??'to';
        $event_title="Acara Spesial Kami";
        if($is_specific&&isset($evt_data['event_name']))$event_title=$evt_data['event_name'];
        elseif(!$is_specific&&isset($config_global['app_name']))$event_title=$config_global['app_name'];
        $lines=explode("\n",$raw_data);$success=0;$duplicate=0;
        foreach($lines as $line){
            $parts=explode("|",$line);
            if(count($parts)>=1&&!empty(trim($parts[0]))){
                $nama=trim($parts[0]);$wa=isset($parts[1])?trim($parts[1]):'';
                $wa_clean=preg_replace('/[^0-9]/','',$wa);
                if(substr($wa_clean,0,1)=='0')$wa_clean='62'.substr($wa_clean,1);
                if(substr($wa_clean,0,2)!='62')$wa_clean='62'.$wa_clean;

                // DUPLICATE CHECK
                $q_check = mysqli_query($koneksi, "SELECT id FROM broadcast_queue WHERE event_id='$selected_event_id' AND nomor_wa='$wa_clean'");
                if(mysqli_num_rows($q_check) > 0){
                    $duplicate++;
                    continue;
                }

                $sep=(strpos($val_link,'?')!==false)?'&':'?';
                $final_link=$val_link.$sep.$param_key.'='.urlencode($nama);
                $msg=str_replace(['[nama-tamu]','[link-undangan]','[tgl]','[event]'],[$nama,$final_link,date('d-m-Y'),$event_title],$val_tmpl);
                $msg_db=mysqli_real_escape_string($koneksi,$msg);
                $final_link_db=mysqli_real_escape_string($koneksi,$final_link);
                
                // INSERT WITH EVENT ID & LINK
                mysqli_query($koneksi,"INSERT INTO broadcast_queue (event_id, nama_tamu, nomor_wa, pesan, status, link_undangan) VALUES ('$selected_event_id', '$nama', '$wa_clean', '$msg_db', 'pending', '$final_link_db')");
                $success++;
            }
        }
        if($success>0 || $duplicate>0){
            $msg_result = "Berhasil generate <span class='tag-success'>$success</span> data.";
            if($duplicate > 0) $msg_result .= " <span class='tag-safe'>($duplicate duplikat dilewati)</span>";
            
            // Jika AJAX
            if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'){
                header('Content-Type: application/json');
                echo json_encode(['status'=>'success', 'msg' => $msg_result]);
                exit;
            }
            $swal_script="ModalAlert.fire('Selesai', '$msg_result', 'success');";
        }
    }
}

// CLEAR QUEUE (Scoped by Event ID)
if(isset($_POST['clear_data'])){
    mysqli_query($koneksi,"DELETE FROM broadcast_queue WHERE event_id='$selected_event_id'");
    $swal_script="Toast.fire({icon: 'success', title: 'Antrian event ini dibersihkan'});";
}

// SINGLE DELETE
if(isset($_POST['hapus_queue'])){
    $id_q=(int)$_POST['id_queue'];
    mysqli_query($koneksi,"DELETE FROM broadcast_queue WHERE id=$id_q AND event_id='$selected_event_id'");
    header("Location: broadcast".($selected_event_id?"?event_id=$selected_event_id":""));exit;
}

// RESET SINGLE
if(isset($_POST['reset_queue_id'])){
    $id_r=(int)$_POST['id_queue'];
    mysqli_query($koneksi,"UPDATE broadcast_queue SET status='pending' WHERE id=$id_r AND event_id='$selected_event_id'");
    header("Location: broadcast?msg=reset_single_ok".($selected_event_id?"&event_id=$selected_event_id":""));exit;
}

// BULK RESET (Scoped by Event ID)
if(isset($_POST['reset_pending'])){
    mysqli_query($koneksi,"UPDATE broadcast_queue SET status='pending' WHERE (status='failed' OR status='sent') AND event_id='$selected_event_id'");
    header("Location: broadcast?msg=reset_ok".($selected_event_id?"&event_id=$selected_event_id":""));exit;
}

// BULK DELETE (Scoped by Event ID)
if(isset($_POST['bulk_delete_target'])){
    $target=$_POST['bulk_delete_target'];
    if($target=='all'){
        mysqli_query($koneksi,"DELETE FROM broadcast_queue WHERE event_id='$selected_event_id'");
    } elseif(in_array($target,['sent','failed','pending'])){
        mysqli_query($koneksi,"DELETE FROM broadcast_queue WHERE status='$target' AND event_id='$selected_event_id'");
    }
    header("Location: broadcast?msg=bulk_del_ok".($selected_event_id?"&event_id=$selected_event_id":""));exit;
}

// MESSAGES
if(isset($_GET['msg'])){
    if($_GET['msg']=='reset_ok')$swal_script="Toast.fire({icon: 'success', title: 'Status antrian di-reset ke Pending'});";
    elseif($_GET['msg']=='bulk_del_ok')$swal_script="Toast.fire({icon: 'success', title: 'Data antrian berhasil dihapus'});";
    elseif($_GET['msg']=='reset_single_ok')$swal_script="Toast.fire({icon: 'success', title: 'Status antrian di-reset ke Pending'});";
}

$master_params=mysqli_query($koneksi,"SELECT * FROM master_broadcast_params");

// LOAD QUEUE (Scoped by Event ID)
$q_stats=mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT COUNT(*) as total, SUM(CASE WHEN status='sent' THEN 1 ELSE 0 END) as sent, SUM(CASE WHEN status='failed' THEN 1 ELSE 0 END) as failed, SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as pending FROM broadcast_queue WHERE event_id='$selected_event_id'"));
$total_queue=$q_stats['total']??0;
$sent_queue=$q_stats['sent']??0;
$failed_queue=$q_stats['failed']??0;
$pending_queue=$q_stats['pending']??0;
$sent_percent=($total_queue>0)?round(($sent_queue/$total_queue)*100):0;
$queue_data=mysqli_query($koneksi,"SELECT * FROM broadcast_queue WHERE event_id='$selected_event_id' ORDER BY id ASC");

// FETCH CATEGORIES FOR SELECTED EVENT
$categories = [];
if (!empty($selected_event_id)) {
    $q_cat = mysqli_query($koneksi, "SELECT DISTINCT kategori FROM tamu WHERE event_id='$selected_event_id' AND kategori != '' ORDER BY kategori ASC");
    while ($c = mysqli_fetch_assoc($q_cat)) {
        $categories[] = $c['kategori'];
    }
}

// GENERATE FROM DATABASE (CATEGORY)
if(isset($_POST['generate_db'])){
    $cat_target = mysqli_real_escape_string($koneksi, $_POST['kategori_target']);
    $evt_target_id = isset($_POST['event_id_target']) ? (int)$_POST['event_id_target'] : (int)$selected_event_id;
    
    $where_cat = "";
    if($cat_target == 'hadir') {
        $where_cat = "WHERE event_id='$evt_target_id' AND checkin_at IS NOT NULL";
    } else {
        $where_cat = ($cat_target == 'all') ? "WHERE event_id='$evt_target_id'" : "WHERE event_id='$evt_target_id' AND kategori='$cat_target'";
    }
    
    $q_tamu_db = mysqli_query($koneksi, "SELECT nama_tamu, no_hp FROM tamu $where_cat");
    
    $pid_used=isset($_POST['param_id'])?(int)$_POST['param_id']:$val_pid;
    $d_p=mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT param_key FROM master_broadcast_params WHERE id='$pid_used'"));
    $param_key=$d_p['param_key']??'to';
    $event_title = $evt_data['event_name'] ?? $config_global['app_name'] ?? 'Acara Kami';
    
    $success = 0; $duplicate = 0;
    while($row_t = mysqli_fetch_assoc($q_tamu_db)){
        $nama = $row_t['nama_tamu'];
        $wa = $row_t['no_hp'];
        if(empty($wa)) continue;
        
        $wa_clean=preg_replace('/[^0-9]/','',$wa);
        if(substr($wa_clean,0,1)=='0')$wa_clean='62'.substr($wa_clean,1);
        if(substr($wa_clean,0,2)!='62')$wa_clean='62'.$wa_clean;

        // DUPLICATE CHECK
        $q_check = mysqli_query($koneksi, "SELECT id FROM broadcast_queue WHERE event_id='$evt_target_id' AND nomor_wa='$wa_clean'");
        if(mysqli_num_rows($q_check) > 0){
            $duplicate++;
            continue;
        }

        $sep=(strpos($val_link,'?')!==false)?'&':'?';
        $final_link=$val_link.$sep.$param_key.'='.urlencode($nama);
        $msg=str_replace(['[nama-tamu]','[link-undangan]','[tgl]','[event]'],[$nama,$final_link,date('d-m-Y'),$event_title],$val_tmpl);
        
        $msg_db=mysqli_real_escape_string($koneksi,$msg);
        $final_link_db=mysqli_real_escape_string($koneksi,$final_link);
        
        // INSERT WITH EVENT ID & LINK
        $q_ins = mysqli_query($koneksi,"INSERT INTO broadcast_queue (event_id, nama_tamu, nomor_wa, pesan, status, link_undangan) VALUES ('$evt_target_id', '".mysqli_real_escape_string($koneksi,$nama)."', '$wa_clean', '$msg_db', 'pending', '$final_link_db')");
        if($q_ins) $success++;
    }
    
    $msg_result = "Berhasil generate <span class='tag-success'>$success</span> data.";
    if($duplicate > 0) $msg_result .= " <span class='tag-safe'>($duplicate duplikat dilewati)</span>";

    // Jika ini request AJAX, return JSON
    if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'){
        header('Content-Type: application/json');
        echo json_encode(['status'=>'success', 'msg' => $msg_result]);
        exit;
    }
    $swal_script="ModalAlert.fire('Selesai', '$msg_result', 'success');";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WA Gateway Manager - <?= htmlspecialchars($config_global['app_name']??'GuestBook') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        tailwind.config={theme:{extend:{colors:{primary:'#87714c',secondary:'#000000',surface:'#fffcf9'},fontFamily:{sans:['Plus Jakarta Sans','sans-serif'],serif:['Playfair Display','serif']}}}}
    </script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #ffffff; background-image: url("https://www.transparenttextures.com/patterns/cream-paper.png"); }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-thumb { background: #87714c; border-radius: 10px; }
        ::-webkit-scrollbar-track { background: transparent; }
        .select2-container .select2-selection--single { height: 48px !important; background-color: #ffffff !important; border: 1px solid #e8e1d5 !important; border-radius: 0.75rem !important; display: flex; align-items: center; }
        .select2-selection__rendered { font-family: 'Plus Jakarta Sans', sans-serif; color: #000000 !important; font-weight: 600; padding-left: 16px !important; }
        .select2-selection__arrow { height: 48px !important; right: 12px !important; }
        .select2-selection__arrow b { border-color: #000000 transparent transparent transparent !important; }
        .select2-dropdown { border: 1px solid #e8e1d5 !important; border-radius: 0.75rem !important; padding: 4px; background-color: #fff !important; }
        .select2-results__option--highlighted[aria-selected] { background-color: #f9f6f0 !important; color: #87714c !important; border-radius: 0.5rem; }
        div:where(.swal2-container) div:where(.swal2-popup) { border-radius: 1rem !important; border: 1px solid #e8e1d5; font-family: 'Plus Jakarta Sans', sans-serif; }
        .tag-success { background-color: #f0fff4; color: #22543d; padding: 0 4px; border-radius: 4px; border: 1px solid #c6f6d5; font-family: monospace; font-weight: 700; }
        .tag-safe { background-color: #fff5f5; color: #9b2c2c; padding: 0 4px; border-radius: 4px; border: 1px solid #fed7d7; font-family: monospace; font-weight: 700; }
        .tag-info { background-color: #f0f9ff; color: #0369a1; padding: 0 4px; border-radius: 4px; border: 1px solid #bae6fd; font-family: monospace; font-weight: 700; }
        .btn-tmpl { background: #faf7f0; border: 1px dashed #e8e1d5; color: #000000; font-size: 10px; font-weight: 700; padding: 6px 4px; border-radius: 8px; transition: .2s; box-shadow: 0 1px 2px rgba(0, 0, 0, .05); display: flex; align-items: center; justify-content: center; gap: 4px; width: 100%; }
        .btn-tmpl:hover { border-color: #87714c; color: #87714c; transform: translateY(-1px); }
    </style>
</head>
<body class="text-[#000000]">
    <?php if(file_exists('sidebar.php')) include 'sidebar.php'; ?>
    <main class="md:ml-64 p-4 lg:p-10 relative min-w-0 overflow-x-hidden">
        <div class="max-w-7xl mx-auto space-y-6">
    <!-- Header Section -->
    <div class="mb-4 lg:mb-5 border-b border-[#d1c7b7] pb-3 no-print">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-[#fffbf2] text-[#87714c] rounded-xl flex items-center justify-center border border-[#e8e1d5] shadow-sm">
                    <iconify-icon icon="solar:plain-bold-duotone" class="text-2xl"></iconify-icon>
                </div>
                <div>
                    <h1 class="text-2xl lg:text-3xl font-bold text-[#000000] font-serif">WA Gateway</h1>
                    <p class="text-[#87714c] mt-1 text-sm">Kirim pesan massal otomatis untuk tamu undangan.</p>
                </div>
            </div>
            
            <div class="w-full md:w-72">
                <label class="block text-xs font-bold text-[#87714c] mb-2 uppercase tracking-widest pl-1">Event Aktif:</label>
                <form action="" method="GET">
                    <?= csrf_field() ?>
                    <select name="event_id" class="w-full select2-event" onchange="this.form.submit()">
                        <option value="">-- Pilih Event --</option>
                        <?php if($q_events_list){mysqli_data_seek($q_events_list,0);while($evt=mysqli_fetch_assoc($q_events_list)): ?><option value="<?= $evt['id'] ?>" <?= ($selected_event_id==$evt['id'])?'selected':'' ?>><?= htmlspecialchars($evt['event_name']) ?></option><?php endwhile;} ?>
                    </select>
                </form>
            </div>
        </div>
    </div>

            <!-- GATEWAY STATUS & TOKEN CARD -->
            <div class="bg-white p-5 md:p-6 rounded-xl shadow-xl shadow-amber-900/5 border border-[#e8e1d5] flex flex-col md:flex-row items-center justify-between gap-6 mb-8 relative overflow-hidden group">
                <!-- Decorative element -->
                <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-amber-50 rounded-full blur-2xl opacity-40 group-hover:scale-110 transition-transform duration-700"></div>
                
                <div class="flex items-center gap-4 shrink-0 relative z-10 w-full md:w-auto">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#faf7f0] to-[#f5f0e6] text-[#87714c] flex items-center justify-center shrink-0 border border-[#e8e1d5] shadow-inner">
                        <iconify-icon icon="solar:shield-check-bold-duotone" width="26"></iconify-icon>
                    </div>
                    <div>
                        <h4 class="font-bold text-[#000000] text-sm md:text-base font-serif">Status Gateway</h4>
                        <div class="flex flex-wrap gap-2 mt-1">
                            <?php if(!empty($val_token)): ?>
                                <span class="inline-flex items-center gap-1.5 text-[9px] font-black bg-green-50 text-green-600 px-2 py-0.5 rounded-full border border-green-100 uppercase tracking-widest">
                                    <iconify-icon icon="solar:check-circle-bold" width="10"></iconify-icon> Ready
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-1.5 text-[9px] font-black bg-red-50 text-red-600 px-2 py-0.5 rounded-full border border-red-100 uppercase tracking-widest">
                                    <iconify-icon icon="solar:close-circle-bold" width="10"></iconify-icon> Empty
                                </span>
                            <?php endif; ?>
                            <?php if($is_specific): ?>
                                <span class="inline-flex items-center gap-1.5 text-[9px] bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-black uppercase tracking-widest border border-amber-200/30">
                                    <iconify-icon icon="solar:star-bold" width="10"></iconify-icon> Event Only
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="flex-1 w-full max-w-2xl relative z-10">
                    <div class="flex items-center gap-3 w-full">
                        <div class="relative flex-1 group/input">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-[#87714c] font-black text-[9px] uppercase tracking-tighter opacity-70 group-focus-within/input:opacity-100 transition-opacity">Token</div>
                            <input type="text" name="wa_token" form="formConfig" value="<?= htmlspecialchars($val_token) ?>" class="w-full pl-16 pr-4 py-3 bg-[#ffffff] border border-[#e8e1d5] focus:border-[#87714c] focus:ring-1 focus:ring-[#87714c]/10 rounded-xl text-xs transition-all font-mono text-[#000000] placeholder-gray-300 shadow-sm" placeholder="Masukkan Token API (Fonnte)">
                        </div>
                        <a href="https://md.fonnte.com/" target="_blank" class="shrink-0 flex items-center gap-2 text-[10px] text-white bg-[#000000] hover:bg-[#4a332d] font-black uppercase tracking-widest transition-all px-5 py-3 rounded-xl border border-[#4a332d] shadow-md hover:shadow-lg active:scale-95">
                            Ambil <iconify-icon icon="solar:link-circle-bold" width="14"></iconify-icon>
                        </a>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 items-stretch">
                <!-- LEFT COLUMN -->
                <div class="xl:col-span-5 flex flex-col gap-6">
                    <!-- SETTINGS CARD -->
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-[#e8e1d5]">
                        <div class="flex items-center gap-3 mb-4 border-b border-[#faf7f0] pb-3">
                            <iconify-icon icon="solar:settings-minimalistic-bold-duotone" class="text-[#87714c] text-xl"></iconify-icon>
                            <h3 class="font-bold text-[#000000] text-lg font-serif">Setting Link Undangan</h3>
                        </div>
                        <form action="" method="POST" class="space-y-4" id="formConfig">
                            <?= csrf_field() ?>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-bold text-[#87714c] mb-2 uppercase">Link Undangan</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400">
                                            <iconify-icon icon="solar:link-circle-bold-duotone" width="18"></iconify-icon>
                                        </div>
                                        <input type="text" name="broadcast_link" value="<?= htmlspecialchars($val_link) ?>" class="w-full pl-10 pr-4 py-2.5 bg-[#ffffff] border border-[#e8e1d5] focus:border-[#87714c] focus:ring-0 rounded-xl text-sm transition-all" placeholder="https://undangan.com/...">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-[#87714c] mb-2 uppercase flex items-center gap-1">Gambar Banner (Opsional)<span class="text-[10px] text-gray-400 font-normal lowercase">(Direct URL)</span></label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400">
                                            <iconify-icon icon="solar:gallery-wide-bold-duotone" width="18"></iconify-icon>
                                        </div>
                                        <input type="text" name="broadcast_img" value="<?= htmlspecialchars($val_img) ?>" class="w-full pl-10 pr-4 py-2.5 bg-[#ffffff] border border-[#e8e1d5] focus:border-[#87714c] focus:ring-0 rounded-xl text-sm transition-all" placeholder="https://website.com/gambar.jpg">
                                    </div>
                                    <p class="text-[10px] text-gray-500 mt-2 ml-1 leading-5 text-justify"><span class="text-[#87714c] font-bold">*</span> Link gambar harus <span class="font-bold">Direct URL</span> (<code class="bg-gray-100 border border-gray-200 text-[#000000] px-1.5 py-0.5 rounded font-mono text-[9px] font-bold">.jpg</code> atau <code class="bg-gray-100 border border-gray-200 text-[#000000] px-1.5 py-0.5 rounded font-mono text-[9px] font-bold">.png</code>).</p>
                                </div>
                                <?php if($role=='admin'): ?>
                                <div>
                                    <label class="block text-xs font-bold text-[#87714c] mb-2 uppercase">Link Video Tutorial (YouTube)</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400">
                                            <iconify-icon icon="solar:play-circle-bold-duotone" width="18"></iconify-icon>
                                        </div>
                                        <input type="text" name="tutorial_link" value="<?= htmlspecialchars($val_tut) ?>" class="w-full pl-10 pr-4 py-2.5 bg-[#ffffff] border border-[#e8e1d5] focus:border-[#87714c] focus:ring-0 rounded-xl text-sm transition-all" placeholder="https://youtube.com/watch?v=...">
                                    </div>
                                </div>
                                <?php endif; ?>
                                <div class="bg-[#ffffff] p-3 rounded-xl border border-[#e8e1d5]">
                                    <div class="flex justify-between items-center mb-2">
                                        <label class="text-xs font-bold text-[#000000] uppercase">Param URL</label>
                                        <?php if($role=='admin'): ?>
                                        <button type="button" onclick="toggleModal('modalAdminParam')" class="flex items-center gap-1 text-[10px] bg-[#87714c] text-white px-2 py-0.5 rounded-lg hover:bg-[#a68b6c] transition font-bold shadow-sm"><iconify-icon icon="solar:settings-bold"></iconify-icon> Kelola</button>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <?php mysqli_data_seek($master_params,0); while($p=mysqli_fetch_assoc($master_params)): ?>
                                        <label class="cursor-pointer">
                                            <input type="radio" name="param_id" value="<?= $p['id'] ?>" class="peer sr-only" <?= ($val_pid==$p['id'])?'checked':'' ?>>
                                            <div class="px-2 py-1 rounded-md border border-[#e8e1d5] bg-white text-gray-500 text-[11px] font-mono transition-all hover:border-[#87714c] peer-checked:bg-[#87714c] peer-checked:text-white peer-checked:border-[#87714c]">?<?= htmlspecialchars($p['param_key']) ?>=</div>
                                        </label>
                                        <?php endwhile; ?>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex justify-between items-end mb-2">
                                        <label class="block text-xs font-bold text-[#87714c] uppercase flex items-center gap-2">Template Pesan <iconify-icon icon="solar:chat-line-bold-duotone"></iconify-icon></label>
                                    </div>
                                    <div class="mb-3">
                                        <div class="grid grid-cols-5 gap-2 mb-3">
                                            <button type="button" onclick="setTemplate('islam')" class="btn-tmpl">Islam</button>
                                            <button type="button" onclick="setTemplate('kristen')" class="btn-tmpl">Kristen</button>
                                            <button type="button" onclick="setTemplate('hindu')" class="btn-tmpl">Hindu</button>
                                            <button type="button" onclick="setTemplate('buddha')" class="btn-tmpl">Buddha</button>
                                            <button type="button" onclick="toggleMoreTemplates()" id="btnMoreTemplates" class="btn-tmpl !bg-[#000000] !text-white !border-[#000000] hover:!bg-[#4a332d]">Lainnya...</button>
                                        </div>
                                        <div id="containerMoreTemplates" class="hidden grid grid-cols-5 gap-2 mb-2 animate-pulse">
                                            <button type="button" onclick="setTemplate('khitan')" class="btn-tmpl">Khitan</button>
                                            <button type="button" onclick="setTemplate('wisuda')" class="btn-tmpl">Wisuda</button>
                                            <button type="button" onclick="setTemplate('ultah')" class="btn-tmpl">Ultah</button>
                                            <button type="button" onclick="setTemplate('peresmian')" class="btn-tmpl">Peresmian</button>
                                            <button type="button" onclick="setTemplate('umum')" class="btn-tmpl">Umum</button>
                                        </div>
                                        <div class="mt-2 text-[10px] bg-red-50 text-red-700 p-2.5 rounded-lg border border-red-200 leading-relaxed flex items-start gap-2">
                                            <iconify-icon icon="solar:danger-circle-bold" class="text-lg shrink-0 mt-0.5"></iconify-icon>
                                            <div><b>JANGAN HAPUS:</b> <span class="tag-safe">[nama-tamu]</span> dan <span class="tag-safe">[link-undangan]</span>.<br>Gunakan <span class="tag-info">[event]</span> untuk menampilkan nama acara/mempelai secara otomatis.</div>
                                        </div>
                                    </div>
                                    <textarea name="pesan_template" id="pesan_template_area" rows="8" class="w-full px-4 py-3 bg-[#ffffff] border border-[#e8e1d5] focus:border-[#87714c] focus:ring-0 rounded-xl text-sm transition-all text-[#000000] leading-relaxed resize-y font-mono" placeholder="Isikan kata pengantar pada kolom ini, atau gunakan template diatas"><?= htmlspecialchars($val_tmpl) ?></textarea>
                                </div>

                            </div>
                            <button type="button" onclick="confirmSaveConfig()" class="w-full mt-4 bg-[#000000] hover:bg-[#4a332d] text-white py-2.5 rounded-xl font-bold text-sm shadow-md transition-all flex justify-center items-center gap-2"><iconify-icon icon="solar:diskette-bold-duotone" width="18"></iconify-icon> Simpan</button>
                            <input type="hidden" name="save_config" value="1">
                        </form>
                    </div>

                    <!-- INPUT DATA TAMU CARD -->
                    <?php if($show_gen||$role=='admin'): ?>
                    <div class="bg-white p-6 rounded-xl shadow-xl shadow-amber-900/5 border border-[#e8e1d5] mt-2">
                        <div class="flex justify-between items-center mb-6 border-b border-[#faf7f0] pb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-[#faf7f0] text-[#87714c] flex items-center justify-center shrink-0 border border-[#f5f0e6]">
                                    <iconify-icon icon="solar:users-group-rounded-bold-duotone" width="22"></iconify-icon>
                                </div>
                                <h3 class="font-bold text-[#000000] text-lg font-serif">Input Data Tamu</h3>
                            </div>
                        </div>
                        <!-- TAB NAVIGATION -->
                        <div class="flex p-1.5 bg-[#faf7f0] rounded-xl mb-8 w-full md:w-fit border border-[#e8e1d5] shadow-inner">
                            <button type="button" onclick="switchBroadTab('manual')" id="tabManual" class="flex-1 md:flex-none px-8 py-2.5 text-[10px] font-black uppercase tracking-widest rounded-lg transition-all whitespace-nowrap bg-white text-[#000000] shadow-sm border border-[#e8e1d5]">MANUAL / CSV</button>
                            <button type="button" onclick="switchBroadTab('database')" id="tabDatabase" class="flex-1 md:flex-none px-8 py-2.5 text-[10px] font-black uppercase tracking-widest rounded-lg transition-all whitespace-nowrap text-gray-400 hover:text-[#87714c]">DARI DATABASE</button>
                        </div>
                        <!-- MANUAL SECTION -->
                        <div id="sectionManual">
                            <form action="" method="POST" enctype="multipart/form-data" class="space-y-4" id="formGenerate">
                                <?= csrf_field() ?>
                                <div>
                                    <label class="block text-xs font-bold text-[#87714c] mb-2 flex items-center gap-2">Manual (Nama | No.WA) <iconify-icon icon="solar:keyboard-bold-duotone"></iconify-icon></label>
                                    <textarea name="input_data" rows="3" class="w-full px-4 py-3 bg-[#ffffff] border border-[#e8e1d5] focus:border-[#87714c] focus:ring-0 rounded-xl text-sm font-mono" placeholder="Andi | 08123456789&#10;Budi | 628987654321"></textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-[#87714c] mb-2 flex items-center gap-2 uppercase">Import CSV <iconify-icon icon="solar:file-text-bold-duotone" width="16"></iconify-icon></label>
                                    <input type="file" name="csv_file" accept=".csv" class="block w-full text-sm text-[#000000] file:mr-4 file:py-3 file:px-6 file:rounded-l-xl file:border-0 file:text-xs file:font-bold file:bg-[#87714c]/10 file:text-[#87714c] hover:file:bg-[#87714c]/20 bg-[#ffffff] border border-[#e8e1d5] rounded-xl cursor-pointer focus:outline-none focus:border-[#87714c] transition-all">
                                </div>
                                <input type="hidden" name="param_id" value="<?= $val_pid ?>">
                                <input type="hidden" name="event_id_target" value="<?= $selected_event_id ?>">
                                <div class="flex gap-3 pt-2">
                                    <button type="button" onclick="confirmGenerate()" class="flex-1 bg-[#87714c] hover:bg-[#000000] text-white py-3 rounded-xl font-bold text-sm shadow-md transition-all flex justify-center items-center gap-2"><iconify-icon icon="solar:magic-stick-3-bold-duotone"></iconify-icon> Generate</button>
                                    <button type="button" onclick="confirmClearQueue()" class="px-4 bg-red-50 text-red-500 rounded-xl font-bold hover:bg-red-100 transition border border-red-100"><iconify-icon icon="solar:trash-bin-trash-bold-duotone" width="20"></iconify-icon></button>
                                </div>
                                <input type="hidden" name="generate_data" id="btn_generate_submit" disabled>
                                <input type="hidden" name="clear_data" id="btn_clear_submit" disabled>
                            </form>
                        </div>
                        <!-- DATABASE SECTION -->
                        <div id="sectionDatabase" class="hidden">
                            <form action="" method="POST" class="space-y-4" id="formGenerateDB">
                                <?= csrf_field() ?>
                                <div>
                                    <label class="block text-xs font-bold text-[#87714c] mb-2 flex items-center gap-2">Pilih Kategori Tamu <iconify-icon icon="solar:tag-bold-duotone"></iconify-icon></label>
                                    <select name="kategori_target" id="kategori_target_select" class="w-full px-4 py-3 bg-[#ffffff] border border-[#e8e1d5] focus:border-[#87714c] focus:ring-0 rounded-xl text-sm font-bold text-[#000000] appearance-none" onchange="checkAutoThankYouTmpl()">
                                        <option value="all">Satu Event (Semua Kategori)</option>
                                        <option value="hadir" class="text-green-600 font-bold">✨ Hanya Tamu Hadir (Thank You Message)</option>
                                        <?php foreach ($categories as $cat): ?>
                                        <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <input type="hidden" name="param_id" value="<?= $val_pid ?>">
                                <input type="hidden" name="event_id_target" value="<?= $selected_event_id ?>">
                                <div class="flex gap-3 pt-2">
                                    <button type="submit" name="generate_db" class="flex-1 bg-[#000000] hover:bg-[#87714c] text-white py-3 rounded-xl font-bold text-sm shadow-md transition-all flex justify-center items-center gap-2"><iconify-icon icon="solar:database-bold-duotone"></iconify-icon> Generate Dari DB</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- RIGHT COLUMN -->
                <div class="xl:col-span-7 flex flex-col gap-6">
                        <div class="bg-white rounded-xl shadow-sm border border-[#e8e1d5] overflow-hidden relative transition-all duration-300 hover:shadow-md shrink-0">
                            <div class="p-5 bg-gradient-to-r from-[#000000] to-[#795548] flex justify-between items-center relative overflow-hidden">
                                <div class="absolute -right-6 -top-10 text-white/5 rotate-12 pointer-events-none">
                                    <iconify-icon icon="solar:book-bookmark-bold-duotone" width="120"></iconify-icon>
                                </div>
                                <div class="flex items-center gap-3 relative z-10">
                                    <div class="bg-white/20 backdrop-blur-sm p-2 rounded-xl text-white shadow-inner">
                                        <iconify-icon icon="solar:lightbulb-bolt-bold-duotone" width="24"></iconify-icon>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-white tracking-wide font-serif">PANDUAN & TIPS</h3>
                                        <p class="text-[11px] text-white/80 font-medium">Cara aman melakukan broadcast</p>
                                    </div>
                                </div>
                                <button onclick="toggleDisc()" id="btnDiscToggle" class="relative z-10 w-8 h-8 flex items-center justify-center rounded-full bg-white/10 hover:bg-white/20 text-white transition-all">
                                    <iconify-icon icon="solar:alt-arrow-down-bold-duotone" width="20"></iconify-icon>
                                </button>
                            </div>
                            <div id="contentDisc" class="bg-white">
                                <div class="p-6 space-y-6">
                                    <div class="relative bg-[#fffbf2] p-5 rounded-xl border border-[#e8e1d5] flex gap-4 items-start shadow-[0_2px_8px_-2px_rgba(197,168,128,0.15)]">
                                        <div class="shrink-0 text-[#87714c] mt-1"><iconify-icon icon="solar:shield-warning-bold-duotone" width="32"></iconify-icon></div>
                                        <div class="relative z-10">
                                            <h4 class="font-bold text-[#000000] text-sm mb-1 font-serif">Penting: Hindari Blokir WhatsApp</h4>
                                            <p class="text-xs text-gray-600 leading-relaxed text-justify">Sistem broadcast tidak menjamin nomor aman 100%. Gunakan <b>jeda pengiriman (delay)</b> minimal 5-10 detik dan jangan mengirim pesan yang sama persis ke ribuan nomor sekaligus. Gunakan variabel <code class="bg-[#87714c]/10 px-1 rounded text-[#000000] font-mono">[nama]</code> agar pesan terlihat personal.</p>
                                        </div>
                                        <iconify-icon icon="solar:quote-up-bold-duotone" class="absolute top-2 right-3 text-6xl text-[#87714c]/10 pointer-events-none"></iconify-icon>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="flex items-start gap-3 group">
                                            <div class="bg-[#ffffff] text-[#87714c] group-hover:bg-[#87714c] group-hover:text-white border border-[#e8e1d5] w-10 h-10 rounded-xl flex items-center justify-center shrink-0 transition-colors shadow-sm font-bold text-sm">1</div>
                                            <div>
                                                <h5 class="text-xs font-bold text-[#000000] uppercase mb-1">Koneksi Provider</h5>
                                                <p class="text-[11px] text-gray-500">Scan QR di dashboard provider (cth: Fonnte) & pastikan status 'Connected'.</p>
                                            </div>
                                        </div>
                                        <div class="flex items-start gap-3 group">
                                            <div class="bg-[#ffffff] text-[#87714c] group-hover:bg-[#87714c] group-hover:text-white border border-[#e8e1d5] w-10 h-10 rounded-xl flex items-center justify-center shrink-0 transition-colors shadow-sm font-bold text-sm">2</div>
                                            <div>
                                                <h5 class="text-xs font-bold text-[#000000] uppercase mb-1">Token API</h5>
                                                <p class="text-[11px] text-gray-500">Salin Token API ke kolom konfigurasi di sebelah kiri. Validasi token terlebih dahulu.</p>
                                            </div>
                                        </div>
                                        <div class="flex items-start gap-3 group">
                                            <div class="bg-[#ffffff] text-[#87714c] group-hover:bg-[#87714c] group-hover:text-white border border-[#e8e1d5] w-10 h-10 rounded-xl flex items-center justify-center shrink-0 transition-colors shadow-sm font-bold text-sm">3</div>
                                            <div>
                                                <h5 class="text-xs font-bold text-[#000000] uppercase mb-1">Generate Data</h5>
                                                <p class="text-[11px] text-gray-500">Input data tamu atau upload CSV, lalu klik 'Generate' untuk membuat antrian.</p>
                                            </div>
                                        </div>
                                        <div class="flex items-start gap-3 group">
                                            <div class="bg-[#ffffff] text-[#87714c] group-hover:bg-[#87714c] group-hover:text-white border border-[#e8e1d5] w-10 h-10 rounded-xl flex items-center justify-center shrink-0 transition-colors shadow-sm font-bold text-sm">4</div>
                                            <div>
                                                <h5 class="text-xs font-bold text-[#000000] uppercase mb-1">Mulai Kirim</h5>
                                                <p class="text-[11px] text-gray-500">Atur jeda waktu, lalu klik tombol hijau. <span class="font-bold text-red-800">JANGAN TUTUP TAB BROWSER!</span></p>
                                            </div>
                                        </div>
                                    </div>
                                    <?php if($embed_url): ?>
                                    <div class="pt-4 border-t border-[#faf7f0]">
                                        <div class="flex items-center justify-between mb-3">
                                            <div class="flex items-center gap-2">
                                                <iconify-icon icon="solar:videocamera-record-bold-duotone" class="text-[#87714c]"></iconify-icon>
                                                <span class="text-xs font-bold text-[#000000] uppercase">Video Tutorial</span>
                                            </div>
                                            <a href="<?= htmlspecialchars($val_tut) ?>" target="_blank" class="text-[10px] bg-[#87714c]/10 text-[#87714c] px-2 py-1 rounded-lg border border-[#87714c]/20 hover:bg-[#87714c] hover:text-white transition flex items-center gap-1 font-bold">
                                                <iconify-icon icon="solar:link-round-bold"></iconify-icon> Buka di YouTube
                                            </a>
                                        </div>
                                        <div class="bg-[#ffffff] p-1 border border-[#e8e1d5] rounded-xl shadow-sm mb-2">
                                            <div class="aspect-video w-full rounded-lg overflow-hidden relative bg-black/5">
                                                <iframe class="w-full h-full" src="<?= $embed_url ?>" frameborder="0" allowfullscreen></iframe>
                                            </div>
                                        </div>
                                        <div class="text-[9px] text-gray-400 font-mono truncate max-w-full italic px-1">
                                            <?= htmlspecialchars($val_tut) ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <!-- ANTRIAN PESAN CARD -->
                    <div class="bg-white rounded-xl shadow-xl shadow-amber-900/5 border border-[#e8e1d5] overflow-hidden flex flex-col h-[75vh] md:h-[655px] mt-2 group/queue">
                        <div class="px-4 py-3 md:px-5 md:py-4 border-b border-[#faf7f0] bg-white flex items-center justify-between z-10 shrink-0">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 md:w-10 md:h-10 rounded-xl bg-[#faf7f0] text-[#87714c] flex items-center justify-center shrink-0 border border-[#f5f0e6]">
                                    <iconify-icon icon="solar:list-check-bold-duotone" class="text-lg md:text-xl"></iconify-icon>
                                </div>
                                <div class="leading-tight">
                                    <h3 class="font-bold text-[#000000] text-sm md:text-base font-serif">Antrian Pesan</h3>
                                    <div class="text-[10px] md:text-xs text-gray-400 font-medium mt-0.5">
                                        Total: <span class="text-[#87714c] font-bold bg-[#faf7f0] px-1.5 rounded border border-[#f5f0e6]"><?= $total_queue ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" onclick="openBulkDeleteModal()" class="h-8 md:h-9 px-2 md:px-3 rounded-lg bg-red-50 text-red-500 border border-red-100 hover:bg-red-100 transition flex items-center gap-1.5 shadow-sm" title="Hapus Opsi">
                                    <iconify-icon icon="solar:trash-bin-trash-bold-duotone" class="text-base md:text-lg"></iconify-icon>
                                    <span class="hidden md:inline text-[11px] font-bold">Hapus</span>
                                </button>
                                <button type="button" onclick="retryFailedBroadcast()" class="h-8 md:h-9 px-2 md:px-3 rounded-lg bg-amber-50 text-amber-600 border border-amber-100 hover:bg-amber-100 transition flex items-center gap-1.5 shadow-sm" title="Kirim Ulang yang Gagal">
                                    <iconify-icon icon="solar:refresh-bold-duotone" class="text-base md:text-lg"></iconify-icon>
                                    <span class="hidden md:inline text-[11px] font-bold">Retry Fail</span>
                                </button>
                                <form action="" method="POST" id="formReset">
                                    <?= csrf_field() ?>
                                    <button type="button" onclick="confirmReset()" class="h-8 md:h-9 px-2 md:px-3 rounded-lg bg-white text-[#000000] border border-[#e8e1d5] hover:bg-gray-50 transition flex items-center gap-1.5 shadow-sm" title="Reset Status">
                                        <iconify-icon icon="solar:restart-bold-duotone" class="text-base md:text-lg"></iconify-icon>
                                        <span class="hidden md:inline text-[11px] font-bold">Reset</span>
                                    </button>
                                    <input type="hidden" name="reset_pending" value="1">
                                    <input type="hidden" name="bulk_delete_target" id="bulk_delete_target">
                                </form>
                                <form id="formBulkDelete" method="POST" style="display:none;"><?= csrf_field() ?><input type="hidden" name="bulk_delete_target" id="val_bulk_del"></form>
                            </div>
                        </div>


                        <!-- SEARCH & FILTER BAR -->
                        <div class="px-5 py-4 bg-white border-b border-[#faf7f0] flex flex-col md:flex-row gap-4 shrink-0">
                            <div class="relative flex-1 group/search">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-[#87714c] group-focus-within/search:scale-110 transition-transform">
                                    <iconify-icon icon="solar:magnifer-bold-duotone" width="20"></iconify-icon>
                                </div>
                                <input type="text" id="searchQueue" placeholder="Cari nama atau nomor..." class="w-full pl-11 pr-4 py-3 bg-[#ffffff] border border-[#e8e1d5] focus:border-[#87714c] focus:ring-1 focus:ring-[#87714c]/10 rounded-xl text-xs transition-all placeholder:text-[10px] md:placeholder:text-xs text-[#000000] shadow-sm">
                            </div>
                            <div class="md:w-48 shrink-0">
                                <div class="relative">
                                    <select id="filterStatus" class="w-full pl-4 pr-10 py-3 bg-[#ffffff] border border-[#e8e1d5] focus:border-[#87714c] focus:ring-1 focus:ring-[#87714c]/10 rounded-xl text-xs font-black text-[#000000] appearance-none outline-none shadow-sm uppercase tracking-wider">
                                        <option value="all">Semua Status</option>
                                        <option value="pending">PENDING</option>
                                        <option value="sent">SENT (Sukses)</option>
                                        <option value="failed">FAILED (Gagal)</option>
                                    </select>
                                    <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-[#87714c]">
                                        <iconify-icon icon="solar:alt-arrow-down-bold" width="16"></iconify-icon>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- PROGRESS BAR -->
                        <div id="broadcastProgress" class="hidden px-4 py-3 bg-[#fffbf2] border-b border-[#e8e1d5] shrink-0">
                            <div class="flex justify-between text-xs font-bold text-[#000000] mb-1">
                                <span id="progressTextInfo">Mengirim...</span>
                                <span id="progressPercent">0%</span>
                            </div>
                            <div class="w-full h-2.5 bg-gray-200 rounded-full overflow-hidden">
                                <div id="progressBar" class="h-full bg-gradient-to-r from-green-500 to-green-600 w-0 transition-all duration-300"></div>
                            </div>
                            <div id="logArea" class="mt-2 text-[10px] text-gray-500 font-mono h-10 overflow-y-auto border border-gray-100 p-1 rounded bg-white">
                                Menunggu perintah...
                            </div>
                        </div>

                        <!-- QUEUE TABLE -->
                        <div class="overflow-y-auto flex-1 p-0 custom-scrollbar relative">
                            <table class="w-full text-left text-sm" id="queueTable">
                                <thead class="bg-[#faf7f0] text-[#000000] text-[11px] font-bold uppercase sticky top-0 z-10 border-b border-[#e8e1d5] hidden md:table-header-group">
                                    <tr>
                                        <th class="px-4 py-3">Tamu</th>
                                        <th class="px-4 py-3">Pesan</th>
                                        <th class="px-4 py-3 text-center">Status</th>
                                        <th class="px-4 py-3 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#ffffff]">
                                    <?php if($total_queue > 0): 
                                        mysqli_data_seek($queue_data, 0); 
                                        while($row = mysqli_fetch_assoc($queue_data)): 
                                            $initial = strtoupper(mb_substr($row['nama_tamu'], 0, 1)); 
                                    ?>
                                    <tr class="group hover:bg-[#fffbf2] transition queue-row flex flex-row items-center justify-between p-3 md:table-row md:p-0 border-b border-[#ffffff] md:border-none" 
                                        id="row-<?= $row['id'] ?>" 
                                        data-id="<?= $row['id'] ?>" 
                                        data-status="<?= $row['status'] ?>">
                                        <td class="flex-1 md:table-cell md:px-4 md:py-2.5 md:border-b border-[#e8e1d5] pr-2 min-w-0 align-middle">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-full bg-[#faf7f0] border border-[#e8e1d5] text-[#87714c] flex items-center justify-center text-xs font-bold shrink-0 shadow-sm">
                                                    <?= $initial ?>
                                                </div>
                                                <div class="flex flex-col min-w-0">
                                                    <div class="font-bold text-[#000000] text-sm truncate"><?= htmlspecialchars($row['nama_tamu']) ?></div>
                                                    <div class="text-[11px] text-[#a1887f] font-mono"><?= htmlspecialchars($row['nomor_wa']) ?></div>
                                                    <div class="md:hidden text-[10px] text-gray-400 truncate mt-0.5">
                                                        <?= htmlspecialchars(substr(str_replace(["\r\n","\r","\n"], ' ', $row['pesan']), 0, 30)) ?>...
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="hidden md:table-cell md:px-4 md:py-2.5 md:border-b border-[#e8e1d5] align-middle">
                                            <div class="text-[11px] text-gray-500 truncate max-w-[180px] bg-[#ffffff] border border-[#e8e1d5] px-2 py-1 rounded-md">
                                                <?= htmlspecialchars(substr(str_replace(["\r\n","\r","\n"], ' ', $row['pesan']), 0, 50)) ?>...
                                            </div>
                                        </td>
                                        <td class="shrink-0 md:table-cell md:px-4 md:py-2.5 text-center md:border-b border-[#e8e1d5] align-middle" id="status-col-<?= $row['id'] ?>">
                                            <?php if($row['status'] == 'sent'): ?>
                                                <span class="inline-flex items-center gap-1 text-green-600 font-bold text-[10px] bg-green-50 px-2 py-0.5 rounded-md border border-green-100 uppercase">
                                                    <iconify-icon icon="solar:check-circle-bold" width="10"></iconify-icon> <span class="hidden md:inline">SENT</span>
                                                </span>
                                            <?php elseif($row['status'] == 'failed'): ?>
                                                <span class="inline-flex items-center gap-1 text-red-600 font-bold text-[10px] bg-red-50 px-2 py-0.5 rounded-md border border-red-100 uppercase">FAIL</span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center gap-1 text-[#a1887f] font-bold text-[10px] bg-[#ffffff] px-2 py-0.5 rounded-md border border-[#e8e1d5] uppercase">PENDING</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="shrink-0 md:table-cell md:px-4 md:py-2.5 text-center md:border-b border-[#e8e1d5] pl-2 align-middle">
                                            <div class="flex items-center justify-center gap-2">
                                                <a href="?reset_queue_id=<?= $row['id'] ?>&event_id=<?= $selected_event_id ?>" class="text-[#a1887f] hover:text-blue-600 hover:bg-blue-50 transition p-1.5 rounded-lg flex justify-center" title="Reset Status">
                                                    <iconify-icon icon="solar:restart-bold-duotone" width="16"></iconify-icon>
                                                </a>
                                                <a href="?hapus_queue=<?= $row['id'] ?>&event_id=<?= $selected_event_id ?>" class="text-[#a1887f] hover:text-red-500 hover:bg-red-50 transition p-1.5 rounded-lg flex justify-center" title="Hapus">
                                                    <iconify-icon icon="solar:trash-bin-minimalistic-bold-duotone" width="16"></iconify-icon>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                    <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="p-8 text-center text-gray-400 italic text-xs">Belum ada antrian data untuk event ini.</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- DELAY CONTROL -->
                        <div class="p-5 md:p-6 border-t border-[#e8e1d5] bg-white rounded-b-xl shrink-0 group/delay">
                            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
                                <div class="lg:col-span-7">
                                    <label class="block text-[11px] font-black text-[#87714c] mb-2 uppercase flex items-center gap-2 tracking-widest">
                                        <iconify-icon icon="solar:clock-circle-bold-duotone" width="14"></iconify-icon>
                                        Pengaturan Jeda <span class="text-[9px] text-[#87714c]/60 font-medium normal-case">(Delay antar pesan)</span>
                                    </label>
                                    <div class="bg-[#ffffff] p-1.5 rounded-xl border border-[#e8e1d5] flex items-center shadow-inner h-[48px] focus-within:border-[#87714c] transition-colors">
                                        <div class="relative shrink-0 h-full">
                                            <select id="delayMode" class="h-full bg-transparent text-xs font-black text-[#000000] border-none focus:ring-0 cursor-pointer pl-4 pr-8 uppercase tracking-tighter" onchange="toggleDelayInputs()">
                                                <option value="fixed">Tetap</option>
                                                <option value="random">Acak</option>
                                            </select>
                                            <div class="absolute inset-y-0 right-2 flex items-center pointer-events-none text-[#87714c] opacity-50">
                                                <iconify-icon icon="solar:alt-arrow-down-bold" width="12"></iconify-icon>
                                            </div>
                                        </div>
                                        <div class="w-px h-6 bg-[#e8e1d5] mx-2"></div>
                                        <div class="flex-1 flex items-center gap-3 px-2 h-full">
                                            <div id="inputFixed" class="w-full">
                                                <div class="flex items-center gap-2">
                                                    <input type="number" id="valMinFixed" value="10" min="1" class="w-full text-center text-sm font-black bg-white rounded-lg border border-[#e8e1d5] py-1.5 focus:ring-1 focus:ring-[#87714c] focus:border-[#87714c] text-[#000000] shadow-sm">
                                                    <span class="text-[10px] font-black text-[#87714c] uppercase">Detik</span>
                                                </div>
                                            </div>
                                            <div id="inputRandom" class="hidden w-full">
                                                <div class="flex items-center gap-2 justify-center">
                                                    <input type="number" id="valMinRand" value="3" class="w-14 text-center text-sm font-black bg-white rounded-lg border border-[#e8e1d5] py-1.5 text-[#000000] shadow-sm">
                                                    <span class="text-[#e8e1d5] font-black">-</span>
                                                    <input type="number" id="valMaxRand" value="10" class="w-14 text-center text-sm font-black bg-white rounded-lg border border-[#e8e1d5] py-1.5 text-[#000000] shadow-sm">
                                                    <span class="text-[10px] font-black text-[#87714c] uppercase ml-1">Detik</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 mt-2 px-1">
                                         <iconify-icon icon="solar:danger-circle-bold" width="12" class="text-amber-500"></iconify-icon>
                                         <p class="text-[9px] font-bold text-amber-600/80 uppercase tracking-tighter">Gunakan delay minimal 10 detik agar akun WA tetap aman.</p>
                                    </div>
                                </div>
                                <div class="lg:col-span-5">
                                    <input type="hidden" id="current_evt_id" value="<?= $selected_event_id ?>">
                                    <button onclick="startGatewayBroadcast()" id="btnStart" class="w-full h-[48px] bg-gradient-to-br from-[#000000] to-[#000000] hover:from-[#000000] hover:to-[#2A1C19] text-white font-black uppercase tracking-widest text-xs rounded-xl shadow-lg shadow-[#000000]/20 flex items-center justify-center gap-3 transition-all transform active:scale-95 border border-[#000000]">
                                        <iconify-icon icon="solar:plain-bold-duotone" width="20"></iconify-icon>
                                        <span>Mulai Broadcast</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div> <!-- End Antrian Card -->
                </div> <!-- End Right Column -->
            </div> <!-- End Grid -->
        </div> <!-- End Max-W -->
    </main>

    <?php if($role == 'admin'): ?>
    <div id="modalAdminParam" class="fixed inset-0 z-[60] hidden flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" onclick="toggleModal('modalAdminParam')"></div>
        <div class="relative w-full max-w-sm bg-white rounded-xl shadow-2xl overflow-hidden animate__animated animate__fadeInUp">
            <div class="px-6 py-4 border-b border-[#e8e1d5] bg-[#faf7f0] flex justify-between items-center">
                <h3 class="font-bold text-lg text-[#000000] font-serif">Kelola Parameter</h3>
                <button onclick="toggleModal('modalAdminParam')" class="text-gray-400 hover:text-red-500 transition">
                    <iconify-icon icon="solar:close-circle-bold-duotone" width="24"></iconify-icon>
                </button>
            </div>
            <form action="" method="POST" class="p-6">
                <div class="mb-4">
                    <label class="block text-xs font-bold text-[#87714c] mb-2 uppercase">Tambah Parameter Baru</label>
                    <div class="flex gap-2">
                        <input type="text" name="new_param" placeholder="cth: to" class="flex-1 px-4 py-2.5 bg-[#ffffff] border border-[#e8e1d5] rounded-xl text-sm text-[#000000] focus:ring-1 focus:ring-[#87714c] outline-none" required>
                        <button type="submit" name="add_param" class="bg-[#87714c] hover:bg-[#b0906a] text-white px-5 rounded-xl font-bold text-sm shadow-sm transition">Add</button>
                    </div>
                </div>
            </form>
            <div class="px-6 pb-6">
                <label class="block text-xs font-bold text-[#87714c] mb-2 uppercase">Daftar Parameter</label>
                <div class="max-h-48 overflow-y-auto custom-scrollbar border border-[#e8e1d5] rounded-xl bg-[#ffffff]">
                    <?php mysqli_data_seek($master_params, 0); 
                    while($mp = mysqli_fetch_assoc($master_params)): ?>
                    <div class="flex justify-between items-center px-4 py-3 border-b border-[#e8e1d5] last:border-0 hover:bg-white transition">
                        <span class="text-sm font-mono text-[#000000] font-bold">?<?= htmlspecialchars($mp['param_key']) ?>=</span>
                        <button onclick="confirmDeleteParam(<?= $mp['id'] ?>)" class="text-red-400 hover:text-red-600 bg-red-50 hover:bg-red-100 p-1.5 rounded-lg transition" title="Hapus">
                            <iconify-icon icon="solar:trash-bin-trash-bold-duotone" width="16"></iconify-icon>
                        </button>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>

const Toast=Swal.mixin({toast:true,position:'top-end',showConfirmButton:false,timer:3000,timerProgressBar:true,background:'#fffcf9',color:'#000000',iconColor:'#87714c',customClass:{popup:'rounded-xl border border-[#e8e1d5] shadow-lg',timerProgressBar:'bg-[#87714c]'}});
const ModalAlert=Swal.mixin({customClass:{popup:'rounded-xl border border-[#e8e1d5] shadow-xl',title:'font-serif text-[#000000] text-xl',htmlContainer:'text-[#000000] text-sm leading-relaxed',confirmButton:'bg-[#000000] text-white px-5 py-2.5 rounded-xl text-sm font-bold shadow-md hover:bg-[#4e342e] focus:outline-none transition-all',cancelButton:'bg-white text-gray-500 border border-[#e8e1d5] px-5 py-2.5 rounded-xl text-sm font-bold hover:bg-gray-50 focus:outline-none transition-all',actions:'gap-3 flex-row-reverse'},buttonsStyling:false,background:'#fffcf9',color:'#000000',iconColor:'#87714c'});
<?= $swal_script ?>
$(document).ready(function(){
    $('.select2-event').select2({placeholder:"Pilih Event...",width:'100%'});
    updateMiniStats();
});
function toggleModal(id){const el=$('#'+id);if(el.hasClass('hidden')){el.removeClass('hidden').addClass('flex');}else{el.addClass('hidden').removeClass('flex');}}
function toggleDisc(){$('#contentDisc').toggleClass('hidden');}
function toggleDelayInputs(){const m=$('#delayMode').val();$('#inputFixed').toggleClass('hidden',m!=='fixed');$('#inputRandom').toggleClass('hidden',m==='fixed');}
function toggleMoreTemplates(){const el=document.getElementById('containerMoreTemplates'),btn=document.getElementById('btnMoreTemplates');if(el.classList.contains('hidden')){el.classList.remove('hidden');btn.innerHTML='Tutup';}else{el.classList.add('hidden');btn.innerHTML='Lainnya...';}}
function setTemplate(t){let m="";switch(t){case'islam':m="Assalamu’alaikum Warahmatullahi Wabarakatuh\n\nKepada Yth. *[nama-tamu]*,\n\nTanpa mengurangi rasa hormat, perkenankan kami mengundang Bapak/Ibu/Saudara/i, teman sekaligus sahabat, untuk menghadiri acara pernikahan kami:\n\n*[event]*\n\nBerikut link undangan kami untuk info lengkap acara:\n[link-undangan]\n\nMerupakan suatu kebahagiaan bagi kami apabila Bapak/Ibu/Saudara/i berkenan untuk hadir dan memberikan doa restu.\n\nWassalamu’alaikum Warahmatullahi Wabarakatuh.";break;case'kristen':m="Shalom,\n\nKepada Yth. *[nama-tamu]*,\n\n“Dan di atas semuanya itu: kenakanlah kasih, sebagai pengikat yang mempersatukan dan menyempurnakan.” (Kolose 3:14)\n\nDengan penuh sukacita, kami mengundang Bapak/Ibu/Saudara/i untuk menghadiri Pemberkatan Pernikahan kami:\n\n*[event]*\n\nInfo lengkap acara dapat dilihat di link berikut:\n[link-undangan]\n\nAtas kehadiran dan doa restu Bapak/Ibu/Saudara/i, kami ucapkan terima kasih.\n\nTuhan Memberkati.";break;case'hindu':m="Om Swastiastu,\n\nKepada Yth. *[nama-tamu]*,\n\nAtas Asung Kertha Wara Nugraha Ida Sang Hyang Widhi Wasa/Tuhan Yang Maha Esa, kami bermaksud menyelenggarakan upacara Pawiwahan (Pernikahan) putra-putri kami:\n\n*[event]*\n\nBerikut link undangan digital kami:\n[link-undangan]\n\nKehadiran dan doa restu Bapak/Ibu/Saudara/i adalah kado terindah bagi kami.\n\nOm Shanti Shanti Shanti Om.";break;case'buddha':m="Namo Buddhaya,\n\nKepada Yth. *[nama-tamu]*,\n\nTanpa mengurangi rasa hormat, kami mengundang Bapak/Ibu/Saudara/i untuk menghadiri resepsi pernikahan kami:\n\n*[event]*\n\nInformasi lengkap mengenai acara dapat dilihat melalui tautan berikut:\n[link-undangan]\n\nMerupakan suatu kehormatan dan kebahagiaan bagi kami apabila Bapak/Ibu/Saudara/i berkenan hadir untuk memberikan doa restu kepada kedua mempelai.\n\nSabbe Satta Bhavantu Sukhitatta.";break;case'khitan':m="Assalamu’alaikum Warahmatullahi Wabarakatuh\n\nKepada Yth. *[nama-tamu]*,\n\nDengan memohon Rahmat dan Ridho Allah SWT, kami bermaksud menyelenggarakan tasyakuran Khitanan putra kami:\n\n*[event]*\n\nBerikut link undangan digital kami:\n[link-undangan]\n\nTiada kata yang dapat kami ungkapkan selain rasa terima kasih atas kehadiran dan doa restu Bapak/Ibu/Saudara/i.\n\nWassalamu’alaikum Warahmatullahi Wabarakatuh.";break;case'wisuda':m="Assalamu’alaikum / Salam Sejahtera,\n\nKepada Yth. *[nama-tamu]*,\n\nDengan penuh rasa syukur, kami mengundang Bapak/Ibu/Saudara/i untuk menghadiri acara Syukuran Wisuda:\n\n*[event]*\n\nDetail acara dapat dilihat pada link berikut:\n[link-undangan]\n\nBesar harapan kami agar Bapak/Ibu/Saudara/i dapat hadir berbagi kebahagiaan bersama kami.\n\nTerima kasih.";break;case'ultah':m="Halo *[nama-tamu]*,\n\nKami mengundang Anda untuk merayakan momen spesial Ulang Tahun kami yang ke-... !\n\nAcaranya akan seru banget kalau kamu bisa datang. Cek detail lokasi dan waktunya di sini ya:\n[link-undangan]\n\nKami tunggu kehadirannya. See you there!";break;case'peresmian':m="Yth. *[nama-tamu]*,\n\nKami mengundang Bapak/Ibu untuk menghadiri acara Syukuran & Peresmian (Grand Opening):\n\n*[event]*\n\nMohon berkenan hadir untuk memberikan doa restu bagi kelancaran usaha kami. Info lengkap lokasi dan waktu:\n[link-undangan]\n\nTerima kasih atas perhatian dan kehadirannya.\n\nHormat kami.";break;case'umum':m="Halo *[nama-tamu]*,\n\nKami mengundang Anda untuk bergabung dalam acara spesial kami:\n\n*[event]*\n\nSilakan buka tautan berikut untuk informasi lengkap:\n[link-undangan]\n\nKami sangat mengharapkan kehadiran Anda. Sampai jumpa di acara!\n\nSalam hangat,\nKami yang mengundang.";break;case'thanks':m="Halo *[nama-tamu]*,\n\nKami sekeluarga mengucapkan terima kasih yang sebesar-besarnya atas kehadiran dan doa restu Bapak/Ibu/Saudara/i di acara *[event]*.\n\nKehadiran Anda sangat berarti bagi kami. Semoga Tuhan membalas setiap kebaikan Anda.\n\nSalam hangat,\nKeluarga Besar";break;}document.getElementById('pesan_template_area').value=m;Toast.fire({icon:'success',title:'Template berhasil dimuat'});}
function checkAutoThankYouTmpl(){const s=document.getElementById('kategori_target_select');if(s.value==='hadir'){ModalAlert.fire({title:'Pesan Terima Kasih?',text:'Apakah Anda ingin memuat template pesan terima kasih otomatis?',icon:'question',showCancelButton:true,confirmButtonText:'Ya, Muat Template'}).then((r)=>{if(r.isConfirmed)setTemplate('thanks');});}}

function switchBroadTab(type){
    const active = 'bg-white text-[#000000] shadow-sm border border-[#e8e1d5]';
    const inactive = 'text-gray-400 hover:text-[#87714c]';
    if(type==='manual'){
        $('#tabManual').addClass(active).removeClass(inactive);
        $('#tabDatabase').addClass(inactive).removeClass(active);
        $('#sectionManual').removeClass('hidden');
        $('#sectionDatabase').addClass('hidden');
    } else {
        $('#tabDatabase').addClass(active).removeClass(inactive);
        $('#tabManual').addClass(inactive).removeClass(active);
        $('#sectionDatabase').removeClass('hidden');
        $('#sectionManual').addClass('hidden');
    }
}
function confirmSaveConfig(){ModalAlert.fire({title:'Simpan Pengaturan?',text:"Pastikan Token WA dan Template pesan sudah benar.",icon:'question',showCancelButton:true,confirmButtonText:'Ya, Simpan'}).then((r)=>{if(r.isConfirmed)$('#formConfig').submit();});}
function confirmGenerate(){
    const f=document.querySelector('input[name="csv_file"]');
    if(f.files.length>0){
        const n=f.files[0].name,e=n.split('.').pop().toLowerCase();
        if(e!=='csv'){ModalAlert.fire('File Tidak Didukung','Harap upload file dengan format .CSV','error');return;}
    }
    ModalAlert.fire({
        title:'Generate Antrian?',
        text:"Sistem akan membuat daftar antrian kirim.",
        icon:'info',
        showCancelButton:true,
        confirmButtonText:'Generate Sekarang'
    }).then((r)=>{
        if(r.isConfirmed){
            const formData = new FormData(document.getElementById('formGenerate'));
            formData.append('generate_data', '1');
            $.ajax({
                url: '',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(res){
                    if(res.status === 'success'){
                        ModalAlert.fire('Selesai', res.msg, 'success');
                        loadQueueTable();
                    } else {
                        ModalAlert.fire('Gagal', res.msg || 'Terjadi kesalahan', 'error');
                    }
                }
            });
        }
    });
}

function loadQueueTable(){
    const evtId = $('#current_evt_id').val();
    $('#queueTable tbody').html('<tr><td colspan="4" class="p-8 text-center"><iconify-icon icon="svg-spinners:ring-resize" width="24"></iconify-icon></td></tr>');
    $.get('?ajax_load_queue=1&event_id=' + evtId, function(html){
        $('#queueTable tbody').html(html);
        updateMiniStats(); // Update dashboard
        applyQueueFilter(); // Re-apply filters
    });
}

// Tambahkan listener untuk form Database agar AJAX juga
$(document).on('submit', '#formGenerateDB', function(e){
    e.preventDefault();
    const btn = $(this).find('button[type="submit"]');
    const oldHtml = btn.html();
    btn.prop('disabled', true).html('<iconify-icon icon="svg-spinners:ring-resize" width="16"></iconify-icon> Loading...');
    
    $.post('', $(this).serialize() + '&generate_db=1', function(res){
        btn.prop('disabled', false).html(oldHtml);
        if(res.status === 'success'){
            ModalAlert.fire('Selesai', res.msg, 'success');
            loadQueueTable();
        } else {
            ModalAlert.fire('Gagal', res.msg || 'Terjadi kesalahan', 'error');
        }
    }, 'json');
});
function confirmClearQueue(){ModalAlert.fire({title:'Hapus Semua Antrian?',text:"Tindakan ini tidak bisa dibatalkan.",icon:'warning',showCancelButton:true,confirmButtonText:'Ya, Hapus Semua',confirmButtonColor:'#d33'}).then((r)=>{if(r.isConfirmed){$('#btn_clear_submit').prop('disabled',false).val('1');$('#formGenerate').submit();}});}
function confirmReset(){ModalAlert.fire({title:'Reset Status?',text:"Status akan dikembalikan menjadi 'Pending'.",icon:'warning',showCancelButton:true,confirmButtonText:'Ya, Reset'}).then((r)=>{if(r.isConfirmed)$('#formReset').submit();});}
function confirmDeleteParam(id){ModalAlert.fire({title:'Hapus Parameter?',icon:'warning',showCancelButton:true,confirmButtonText:'Hapus',confirmButtonColor:'#d33'}).then((r)=>{if(r.isConfirmed)window.location.href="?del_param="+id;});}

// CUSTOM UI BULK DELETE
function openBulkDeleteModal(){
    Swal.fire({
        title: 'Hapus Masal Antrian',
        text: 'Pilih status data yang ingin dihapus:',
        html: `
            <div class="mt-4 text-left">
                <label class="block text-xs font-bold text-[#87714c] mb-2 uppercase">Pilih Opsi</label>
                <div class="relative">
                    <select id="swal-bulk-select" class="w-full px-4 py-3 bg-[#ffffff] border border-[#e8e1d5] text-[#000000] text-sm rounded-xl focus:ring-1 focus:ring-[#87714c] focus:border-[#87714c] outline-none appearance-none font-bold">
                        <option value="" disabled selected>-- Pilih Target Hapus --</option>
                        <option value="sent">Hapus yang TERKIRIM (Sent)</option>
                        <option value="failed">Hapus yang GAGAL (Failed)</option>
                        <option value="pending">Hapus yang PENDING</option>
                        <option value="all">Hapus SEMUA DATA (Reset Total)</option>
                    </select>
                    <div class="absolute inset-y-0 right-0 flex items-center px-3 pointer-events-none text-[#000000]">
                        <iconify-icon icon="solar:alt-arrow-down-bold" width="16"></iconify-icon>
                    </div>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Lakukan Hapus',
        confirmButtonColor: '#000000',
        cancelButtonText: 'Batal',
        customClass: {
            popup: 'rounded-xl border border-[#e8e1d5] shadow-xl bg-white font-sans',
            title: 'font-serif text-[#000000] text-xl',
            htmlContainer: 'text-[#000000] text-sm',
            confirmButton: 'bg-[#000000] text-white px-5 py-2.5 rounded-xl text-sm font-bold shadow-md hover:bg-[#4e342e] transition-all',
            cancelButton: 'bg-white text-gray-500 border border-[#e8e1d5] px-5 py-2.5 rounded-xl text-sm font-bold hover:bg-gray-50 transition-all'
        },
        preConfirm: () => {
            const val = document.getElementById('swal-bulk-select').value;
            if (!val) {
                Swal.showValidationMessage('Silakan pilih salah satu opsi');
            }
            return val;
        }
    }).then((r) => {
        if(r.isConfirmed && r.value){
            $('#val_bulk_del').val(r.value);
            $('#formBulkDelete').submit();
        }
    });
}

function updateMiniStats(){
    const rows = $('.queue-row');
    const total = rows.length;
    let sent = 0, failed = 0, pending = 0;
    rows.each(function(){
        const s = $(this).attr('data-status');
        if(s==='sent') sent++;
        else if(s==='failed') failed++;
        else pending++;
    });

    $('.text-\\[#87714c\\].font-bold.bg-\\[#faf7f0\\]').text(total);
}

let isBroadcasting=false;
// REAL-TIME SEARCH & FILTER
function applyQueueFilter(){
    const query = $('#searchQueue').val().toLowerCase();
    const status = $('#filterStatus').val();
    let visibleCount = 0;

    $('.queue-row').each(function(){
        const name = $(this).find('.font-bold.text-\\[\\#000000\\]').text().toLowerCase();
        const wa = $(this).find('.text-\\[11px\\].text-\\[\\#a1887f\\]').text().toLowerCase();
        const rowStatus = $(this).attr('data-status');
        
        const matchSearch = name.includes(query) || wa.includes(query);
        const matchStatus = (status === 'all') || (rowStatus === status);

        if(matchSearch && matchStatus){
            $(this).removeClass('hidden').addClass('flex md:table-row');
            visibleCount++;
        } else {
            $(this).addClass('hidden').removeClass('flex md:table-row');
        }
    });

    // Update Showing Text if needed, or just let users see the rows
    if(visibleCount === 0 && $('.queue-row').length > 0){
        if($('#emptyFilterMsg').length === 0){
            $('#queueTable tbody').append('<tr id="emptyFilterMsg"><td colspan="4" class="p-8 text-center text-gray-400 italic text-xs">Tidak ada data yang cocok dengan filter.</td></tr>');
        }
    } else {
        $('#emptyFilterMsg').remove();
    }
}

$(document).on('input', '#searchQueue', applyQueueFilter);
$(document).on('change', '#filterStatus', applyQueueFilter);
async function retryFailedBroadcast() {
    if(isBroadcasting) return;
    const failedRows = document.querySelectorAll('.queue-row[data-status="failed"]');
    if(failedRows.length === 0) {
        ModalAlert.fire('Info', 'Tidak ada antrian yang gagal.', 'info');
        return;
    }

    const c = await ModalAlert.fire({
        title: 'Kirim Ulang Gagal?',
        text: `Akan mengirim ulang ${failedRows.length} pesan yang gagal sebelumnya.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Kirim'
    });
    if(!c.isConfirmed) return;

    const eId = $('#current_evt_id').val();
    $.post('', {ajax_reset_failed: 1, event_id: eId}, async function(res){
        if(res.status === 'success'){
            failedRows.forEach(r => {
                const id = r.getAttribute('data-id');
                const st = document.getElementById(`status-col-${id}`);
                r.setAttribute('data-status', 'pending');
                st.innerHTML = '<span class="inline-flex items-center gap-1 text-[#a1887f] font-bold text-[10px] bg-[#ffffff] px-2 py-0.5 rounded-md border border-[#e8e1d5] uppercase">PENDING</span>';
            });
            applyQueueFilter();
            startGatewayBroadcast();
        } else {
            ModalAlert.fire('Gagal', res.msg || 'Gagal mereset status', 'error');
        }
    }, 'json');
}

async function startGatewayBroadcast(){
    if(isBroadcasting) return;
    const rows = document.querySelectorAll('.queue-row[data-status="pending"]');
    if(rows.length === 0){
        ModalAlert.fire('Info','Tidak ada antrian pending.','info');
        return;
    }
    const c = await ModalAlert.fire({
        title: 'Mulai Broadcast?',
        text: `Mengirim ${rows.length} pesan. JANGAN TUTUP TAB.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Mulai Kirim'
    });
    if(!c.isConfirmed) return;
    
    isBroadcasting = true;
    document.getElementById('btnStart').innerHTML = `<iconify-icon icon="svg-spinners:ring-resize" width="20"></iconify-icon> Mengirim...`;
    document.getElementById('broadcastProgress').classList.remove('hidden');
    
    const eId = document.getElementById('current_evt_id').value;
    const log = document.getElementById('logArea');
    let s=0, f=0;
    
    for(let i=0; i<rows.length; i++){
        const r = rows[i];
        const id = r.getAttribute('data-id');
        const st = document.getElementById(`status-col-${id}`);
        
        r.classList.add('bg-yellow-50');
        st.innerHTML = `<span class="text-blue-500 text-[10px] font-bold animate-pulse">SENDING...</span>`;
        r.scrollIntoView({behavior:'smooth', block:'center'});
        
        try {
            const fd = new FormData();
            fd.append('ajax_send_id', id);
            fd.append('current_event_id', eId);
            const res = await fetch('', {method:'POST', body:fd});
            const json = await res.json();
            
            if(json.status === 'success'){
                st.innerHTML = `<span class="inline-flex items-center gap-1 text-green-600 font-bold text-[10px] bg-green-50 px-2.5 py-1 rounded-full border border-green-100"><iconify-icon icon="solar:check-circle-bold" width="12"></iconify-icon> <span class="hidden md:inline">SENT</span></span>`;
                r.setAttribute('data-status', 'sent');
                s++;
            } else {
                st.innerHTML = `<span class="inline-flex items-center gap-1 text-red-600 font-bold text-[10px] bg-red-50 px-2.5 py-1 rounded-full border border-red-100">FAILED</span>`;
                log.innerHTML = `<span class="text-red-500">ID ${id}: ${json.msg}</span><br>` + log.innerHTML;
                r.setAttribute('data-status', 'failed');
                f++;
            }
        } catch(e) {
            st.innerHTML = `<span class="text-red-600 font-bold text-[10px]">ERR</span>`;
            f++;
        }
        
        r.classList.remove('bg-yellow-50');
        updateMiniStats();
        applyQueueFilter();
        
        const p = Math.round(((i+1)/rows.length)*100);
        document.getElementById('progressBar').style.width = `${p}%`;
        document.getElementById('progressPercent').innerText = `${p}%`;
        
        if(i < rows.length-1){
            const m = document.getElementById('delayMode').value;
            let d = (m === 'fixed') 
                ? parseInt(document.getElementById('valMinFixed').value)*1000 
                : Math.floor(Math.random()*(parseInt(document.getElementById('valMaxRand').value)-parseInt(document.getElementById('valMinRand').value)+1)+parseInt(document.getElementById('valMinRand').value))*1000;
            await new Promise(r => setTimeout(r, d));
        }
    }
    
    isBroadcasting = false;
    document.getElementById('btnStart').innerHTML = `<iconify-icon icon="solar:plain-bold-duotone" width="20"></iconify-icon> <span>Selesai</span>`;
    ModalAlert.fire('Selesai', `Sukses: ${s}, Gagal: ${f}`, 'success');
}
function actionQueue(type, id) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '';
    const csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = 'csrf_token';
    csrf.value = '<?= $_SESSION['csrf_token'] ?>';
    form.appendChild(csrf);
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = type === 'reset' ? 'reset_queue_id' : 'hapus_queue';
    actionInput.value = '1';
    form.appendChild(actionInput);
    const idInput = document.createElement('input');
    idInput.type = 'hidden';
    idInput.name = 'id_queue';
    idInput.value = id;
    form.appendChild(idInput);
    document.body.appendChild(form);
    form.submit();
}
</script>
<footer class="mt-12 mb-6 text-center text-xs text-gray-400 border-t border-gray-100 pt-6">
    <?= $config_global['copyright'] ?? $config['copyright'] ?? '© ' . date('Y') . ' BUKU TAMU DIGITAL Eksklusif' ?>
</footer>
</main></body></html>