<?php include 'db.php'; ?>
<!DOCTYPE html><html><head><title>Player</title>
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
<link rel="stylesheet" href="user_style.css"><script src="darkmode.js"></script>
<script>
const token = localStorage.getItem('player_token');
if (!token) { window.location.href = 'index.php'; }
let currentRn = 0;

async function refresh() {
    if (document.activeElement && document.activeElement.id === 'ans-i') return;
    try {
        const res = await fetch(`api.php?action=get_state&token=${token}`);
        const d = await res.json();
        if (d.error === 'not_found') { window.location.href = 'index.php'; return; }

        document.getElementById('display-name').innerText = d.player_name;
        document.getElementById('score-val').innerText = d.score;
        document.getElementById('round-num-user').innerText = 'ROUND: ' + d.round_number;

        if (d.round_number !== currentRn) { 
            currentRn = d.round_number; 
            const inp = document.getElementById('ans-i'); if(inp) inp.value = ''; 
        }

        const myWord = (d.my_ans || "").toUpperCase();
        const boxL = document.getElementById('box-l');
        const boxR = document.getElementById('box-r');

        if (d.status === 'waiting') {
            boxL.innerText = ""; boxR.innerText = "";
            document.getElementById('msg').innerText = "Waiting for Game Master...";
        } else {
            boxL.innerHTML = d.word_left ? d.word_left : (myWord ? `<u>${myWord}</u>` : "");
            boxR.innerHTML = d.word_right ? d.word_right : (myWord ? `<u>${myWord}</u>` : "");
            document.getElementById('msg').innerText = d.status === 'active' ? (myWord ? "WORD SAVED" : "Round Active!") : "ROUND SCORED";
        }

        const area = document.getElementById('game-area');
        if (d.status === 'active') {
            if (!document.getElementById('ans-i')) {
                area.innerHTML = `<input type="text" id="ans-i" placeholder="ENTER WORD..." style="text-transform:uppercase" oninput="this.value=this.value.toUpperCase()"><button onclick="sub()">SUBMIT</button>`;
            }
        } else { area.innerHTML = ''; }
        
        let lHtml = '<b>Leaderboard</b><br>';
        (d.leaderboard || []).forEach(s => { lHtml += `<small>${s.name}: ${s.total_score}</small><br>`; });
        document.getElementById('user-leaderboard').innerHTML = lHtml;
    } catch(e) {}
}
async function sub() {
    const v = document.getElementById('ans-i').value.trim().toUpperCase();
    if(!v) return;
    await fetch(`api.php?action=submit&token=${token}&ans=${encodeURIComponent(v)}`);
    refresh();
}
setInterval(refresh, 2500); window.onload = refresh;
</script></head>
<body><div class="container">
    <div class="header-top">
        <div><span id="display-name" style="font-weight:bold;">...</span> <span id="round-num-user" style="margin-left:10px; font-size:0.8em; color:#007aff;">ROUND: 1</span></div>
        <span>Score: <b id="score-val" style="color:#007aff">0</b></span>
    </div>
    <div class="prompt-container"><div id="box-l" class="prompt-box"></div><div id="box-r" class="prompt-box"></div></div>
    <div id="msg" style="text-align:center; font-weight:bold; margin-bottom:15px; font-size:0.9em; color:#007aff;"></div>
    <div id="game-area"></div>
    <div id="user-leaderboard" style="margin-top:20px; border-top:1px solid #eee; padding-top:10px; font-size:0.8em;"></div>
</div><button id="theme-toggle-btn" class="theme-btn" onclick="toggleTheme()">🌙</button></body></html>