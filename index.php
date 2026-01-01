<!DOCTYPE html><html><head><title>Join Blank Slate</title>
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
<link rel="stylesheet" href="user_style.css"></head>
<body><div class="container" style="text-align:center; margin-top:20%;">
    <h2>BLANK SLATE</h2>
    <input type="text" id="p-name" placeholder="YOUR NAME" style="text-transform:uppercase;">
    <button onclick="join()">JOIN GAME</button>
    <script>
    async function join() {
        let n = document.getElementById('p-name').value.trim();
        if(!n) return;
        let res = await fetch('api.php?action=register&name=' + encodeURIComponent(n));
        let d = await res.json();
        if(d.token) {
            localStorage.setItem('player_token', d.token);
            window.location.href = 'user.php';
        }
    }
    </script>
</div></body></html>