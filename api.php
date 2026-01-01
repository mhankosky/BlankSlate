<?php
include 'db.php';
header('Content-Type: application/json');

try {
    $action = $_GET['action'] ?? '';
    $st = $conn->query("SELECT * FROM game_state WHERE id=1")->fetch_assoc();
    $sett = $conn->query("SELECT * FROM game_settings WHERE id=1")->fetch_assoc();
    $rn = (int)$st['round_number'];

    if ($action === 'register') {
        $n = strtoupper($conn->real_escape_string($_GET['name']));
        $t = bin2hex(random_bytes(16));
        $conn->query("INSERT INTO players (name, token, total_score) VALUES ('$n', '$t', 0)");
        echo json_encode(['success' => true, 'token' => $t]);
    }
    elseif ($action === 'submit') {
        $t = $conn->real_escape_string($_GET['token'] ?? '');
        $ans = strtoupper(trim($_GET['ans'] ?? ''));
        if ($sett['allow_spaces'] == 0 && strpos($ans, ' ') !== false) {
            echo json_encode(['success' => false, 'error' => 'SPACES NOT ALLOWED']); exit;
        }
        $pl = $conn->query("SELECT id FROM players WHERE token='$t'")->fetch_assoc();
        if ($pl && $st['status'] === 'active') {
            $conn->query("INSERT INTO answers (player_id, round_number, answer_text) VALUES (".$pl['id'].", $rn, '$ans') ON DUPLICATE KEY UPDATE answer_text='$ans'");
            echo json_encode(['success' => true]);
        }
    }
    elseif ($action === 'admin_data') {
        $res = $conn->query("SELECT p.name, a.answer_text, a.points_earned FROM players p LEFT JOIN answers a ON p.id = a.player_id AND a.round_number = $rn WHERE p.hidden=0");
        $ans_list = []; $counts = []; $temp = [];
        while($r = $res->fetch_assoc()) { 
            $val = strtoupper(trim($r['answer_text'] ?? ''));
            $temp[] = ['name' => $r['name'], 'ans' => $val, 'pts' => (int)$r['points_earned']];
            if(!empty($val)) @$counts[$val]++; 
        }
        foreach($temp as $row) {
            $c = $counts[$row['ans']] ?? 0;
            $live_pts = ($c == 2) ? 3 : (($c >= 3) ? 1 : 0);
            $ans_list[] = ['name'=>$row['name'], 'ans'=>$row['ans']?:'...', 'count'=>$c, 'pts'=>($st['status']==='scored'?$row['pts']:$live_pts)];
        }
        $scores = $conn->query("SELECT name, total_score FROM players WHERE hidden=0 ORDER BY total_score DESC")->fetch_all(MYSQLI_ASSOC);
        $history = [];
        $h_res = $conn->query("SELECT * FROM round_history ORDER BY round_number DESC LIMIT 10");
        while($h = $h_res->fetch_assoc()) {
            $h_rn = $h['round_number'];
            $p_res = $conn->query("SELECT p.name, a.answer_text as ans, a.points_earned as pts FROM players p JOIN answers a ON p.id = a.player_id WHERE a.round_number = $h_rn AND p.hidden = 0");
            $h['player_results'] = $p_res->fetch_all(MYSQLI_ASSOC);
            $history[] = $h;
        }
        echo json_encode(['status'=>$st['status'], 'round_number'=>$rn, 'word_left'=>strtoupper($st['word_left']??''), 'word_right'=>strtoupper($st['word_right']??''), 'answers'=>$ans_list, 'scores'=>$scores, 'history'=>$history, 'settings'=>$sett]);
    }
    elseif ($action === 'get_state') {
        $t = $conn->real_escape_string($_GET['token'] ?? '');
        $pl = $conn->query("SELECT id, name, total_score FROM players WHERE token='$t'")->fetch_assoc();
        if (!$pl) { echo json_encode(['error' => 'not_found']); exit; }
        $my_ans = $conn->query("SELECT answer_text FROM answers WHERE player_id=".$pl['id']." AND round_number=$rn")->fetch_assoc();
        $lb = $conn->query("SELECT name, total_score FROM players WHERE hidden=0 ORDER BY total_score DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['status'=>$st['status'], 'player_name'=>$pl['name'], 'score'=>$pl['total_score'], 'round_number'=>$rn, 'my_ans'=>strtoupper($my_ans['answer_text']??''), 'word_left'=>strtoupper($st['word_left']??''), 'word_right'=>strtoupper($st['word_right']??''), 'leaderboard'=>$lb]);
    }
    elseif ($action === 'start_round') {
        $wl = strtoupper($conn->real_escape_string($_GET['wl'])); $wr = strtoupper($conn->real_escape_string($_GET['wr']));
        $conn->query("UPDATE game_state SET status='active', word_left='$wl', word_right='$wr' WHERE id=1");
        $conn->query("INSERT INTO round_history (round_number, word_left, word_right) VALUES ($rn, '$wl', '$wr') ON DUPLICATE KEY UPDATE word_left='$wl', word_right='$wr'");
        echo json_encode(['success' => true]);
    }
    elseif ($action === 'lock_score') {
        $all = $conn->query("SELECT player_id, answer_text FROM answers WHERE round_number=$rn AND answer_text != ''")->fetch_all(MYSQLI_ASSOC);
        $cts = array_count_values(array_map(fn($v) => strtoupper(trim($v)), array_column($all, 'answer_text')));
        foreach($all as $row) {
            $val = strtoupper(trim($row['answer_text']));
            $pts = ($cts[$val] == 2) ? 3 : (($cts[$val] >= 3) ? 1 : 0);
            $conn->query("UPDATE answers SET points_earned=$pts WHERE player_id=".$row['player_id']." AND round_number=$rn");
            $conn->query("UPDATE players SET total_score = total_score + $pts WHERE id=".$row['player_id']);
        }
        $conn->query("UPDATE game_state SET status='scored' WHERE id=1");
        echo json_encode(['success' => true]);
    }
    elseif ($action === 'next_round') {
        $conn->query("UPDATE game_state SET status='waiting', round_number=".($rn+1).", word_left='', word_right='' WHERE id=1");
        echo json_encode(['success' => true]);
    }
    elseif ($action === 'update_settings') {
        $conn->query("UPDATE game_settings SET allow_spaces=".(int)$_GET['allow_spaces']." WHERE id=1");
        echo json_encode(['success' => true]);
    }
    elseif ($action === 'reset_scores') {
        $conn->query("UPDATE players SET total_score=0");
        $conn->query("TRUNCATE answers");
        $conn->query("TRUNCATE round_history");
        $conn->query("UPDATE game_state SET round_number=1, status='waiting', word_left='', word_right='' WHERE id=1");
        echo json_encode(['success' => true]);
    }
    elseif ($action === 'reset_game') {
        $conn->query("SET FOREIGN_KEY_CHECKS=0");
        $conn->query("TRUNCATE answers");
        $conn->query("TRUNCATE players");
        $conn->query("TRUNCATE round_history");
        $conn->query("SET FOREIGN_KEY_CHECKS=1");
        $conn->query("UPDATE game_state SET round_number=1, status='waiting', word_left='', word_right='' WHERE id=1");
        echo json_encode(['success' => true]);
    }
} catch (Exception $e) { echo json_encode(['error' => 'server_error']); }
?>