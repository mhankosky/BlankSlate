<?php include 'db.php'; ?>
<!DOCTYPE html><html><head><title>Admin Control</title>
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
<link rel="stylesheet" href="admin_style.css"><script src="darkmode.js"></script>
<script>
function validateInputs() {
    const l = document.getElementById('wl').value.trim();
    const r = document.getElementById('wr').value.trim();
    const btn = document.getElementById('start-btn');
    const msg = document.getElementById('helper-text');
    const isValid = (l === "" && r !== "") || (l !== "" && r === "");
    if(!msg) return;
    msg.innerText = (l !== "" && r !== "") ? "Only one box can contain text!" : (isValid ? "Ready to start!" : "Enter text in one box to begin");
    msg.style.color = (l !== "" && r !== "") ? "#dc3545" : (isValid ? "#28a745" : "#888");
    btn.disabled = !isValid;
}

async function refresh() {
    try {
        const res = await fetch('api.php?action=admin_data');
        const d = await res.json();
        
        document.getElementById('round-num').innerText = 'ROUND: ' + d.round_number;
        document.getElementById('stat-text').innerText = 'STATUS: ' + d.status.toUpperCase();
        document.getElementById('wait-ui').style.display = d.status === 'waiting' ? 'block' : 'none';
        document.getElementById('active-ui').style.display = d.status === 'active' ? 'block' : 'none';
        document.getElementById('scored-ui').style.display = d.status === 'scored' ? 'block' : 'none';
        
        if(d.status !== 'waiting') {
            document.getElementById('admin-prompts').style.display = 'flex';
            document.getElementById('box-l').innerText = d.word_left;
            document.getElementById('box-r').innerText = d.word_right;
        } else { 
            document.getElementById('admin-prompts').style.display = 'none'; 
            validateInputs(); 
        }

        document.getElementById('space-toggle').checked = d.settings.allow_spaces == 1;

        let aHtml = '';
        (d.answers || []).forEach(a => {
            let cls = 'no-match'; if(a.ans !== '...') { if(a.count === 2) cls = 'match-2'; else if(a.count >= 3) cls = 'match-3'; }
            let hTxt = a.hidden ? 'UNHIDE' : 'HIDE';
            aHtml += `<div class="ans-row">
                <span style="position:relative;">
                    <span class="gear-icon" onclick="togglePlayerMenu(${a.id}, event)">⚙️</span>
                    ${a.name}
                    <div id="player-menu-${a.id}" class="player-dropdown">
                        <div onclick="editPl(${a.id},'${a.name}')">Edit Name</div>
                        <div onclick="doAct('toggle_hide&id=${a.id}')">${hTxt}</div>
                        <div onclick="remPl(${a.id})" class="danger">Delete</div>
                    </div>
                </span>
                <span class="${cls}">${a.ans} <small class="pts-pill">+${a.pts}</small></span>
            </div>`;
        });
        document.getElementById('ans-list').innerHTML = aHtml;

        let lHtml = '';
        (d.scores || []).forEach(s => { if(!s.hidden) lHtml += `<div class="ans-row"><span>${s.name}</span><span>${s.total_score}</span></div>`; });
        document.getElementById('lead-list').innerHTML = lHtml;

        if(d.history && d.history.length > 0) {
            let players = d.history[0].player_results.map(p => p.name);
            let h = `<table class="history-table"><thead><tr><th>Rd</th><th>L</th><th>R</th>`;
            players.forEach(p => h += `<th>${p}</th>`);
            h += `</tr></thead><tbody>`;
            d.history.forEach(row => {
                h += `<tr><td>${row.round_number}</td><td>${row.word_left}</td><td>${row.word_right}</td>`;
                players.forEach(pName => {
                    let p = row.player_results.find(x => x.name === pName);
                    h += `<td>${p ? p.ans + '<br><small>+' + p.pts + '</small>' : '-'}</td>`;
                });
                h += `</tr>`;
            });
            document.getElementById('history-view').innerHTML = h + `</tbody></table>`;
        }
    } catch(e) {}
}

function togglePlayerMenu(id, e) {
    e.stopPropagation();
    const menu = document.getElementById('player-menu-'+id);
    const visible = menu.style.display === 'block';
    document.querySelectorAll('.player-dropdown').forEach(m => m.style.display = 'none');
    menu.style.display = visible ? 'none' : 'block';
}

function editPl(id, old) { let n = prompt("New Name:", old); if(n) doAct('edit_player&id='+id+'&name='+encodeURIComponent(n)); }
function remPl(id) { if(confirm("Delete player?")) doAct('remove_player&id='+id); }

async function doAct(a){ 
    await fetch('api.php?action='+a); 
    if(a.includes('next_round')) { document.getElementById('wl').value=''; document.getElementById('wr').value=''; }
    refresh(); 
}

window.onclick = function() { document.querySelectorAll('.player-dropdown').forEach(m => m.style.display = 'none'); }
setInterval(refresh, 3000); window.onload = refresh;
</script></head>
<body><div class="container">
    <div class="header"><b>GM CONTROL <span id="round-num">ROUND: 1</span></b><button onclick="document.getElementById('menu').style.display='block'" style="width:auto; padding:5px 10px;">MENU</button></div>
    <div id="stat-text" class="status-bar">LOADING...</div>
    <div id="admin-prompts" class="prompt-container" style="display:none;"><div id="box-l" class="prompt-box"></div><div id="box-r" class="prompt-box"></div></div>
    
    <div id="wait-ui" style="display:none; text-align:center;">
        <p id="helper-text" style="font-size:0.8em; font-weight:bold; margin-bottom:5px;">Enter text in one box</p>
        <div class="admin-input-row"><input type="text" id="wl" placeholder="LEFT" oninput="validateInputs()"><input type="text" id="wr" placeholder="RIGHT" oninput="validateInputs()"></div>
        <button id="start-btn" onclick="doAct('start_round&wl='+document.getElementById('wl').value.toUpperCase()+'&wr='+document.getElementById('wr').value.toUpperCase())">START ROUND</button>
    </div>
    <div id="active-ui" style="display:none;"><button style="background:green" onclick="doAct('lock_score')">LOCK & SCORE</button></div>
    <div id="scored-ui" style="display:none;"><button style="background:#666" onclick="doAct('next_round')">NEXT ROUND</button></div>

    <div class="score-grid">
        <div><h3>Answers</h3><div id="ans-list"></div></div>
        <div><h3>Leaderboard</h3><div id="lead-list"></div></div>
    </div>

    <button style="margin-top:20px; background:#444;" onclick="let v=document.getElementById('history-cont'); v.style.display=v.style.display==='none'?'block':'none'">HISTORY TABLE</button>
    <div id="history-cont" style="display:none; overflow-x:auto; margin-top:10px;"><div id="history-view"></div></div>

    <div id="menu" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:10000;">
        <div class="container" style="margin:10% auto; max-width:320px;">
            <h3>Settings</h3>
            <label style="display:flex; justify-content:space-between; margin-bottom:10px; align-items:center;">Allow Spaces <input type="checkbox" id="space-toggle" onchange="fetch('api.php?action=update_settings&allow_spaces='+(this.checked?1:0))"></label>
            <button onclick="if(confirm('Reset Scores?')) { doAct('reset_scores'); document.getElementById('menu').style.display='none'; }">Reset Scores</button>
            <button style="background:red; margin-top:5px;" onclick="if(confirm('Wipe Everything?')) { doAct('reset_game'); document.getElementById('menu').style.display='none'; }">Full Reset</button>
            <button style="background:#444; margin-top:15px;" onclick="document.getElementById('menu').style.display='none'">CLOSE</button>
        </div>
    </div>
</div><button id="theme-toggle-btn" class="theme-btn" onclick="toggleTheme()">🌙</button></body></html>