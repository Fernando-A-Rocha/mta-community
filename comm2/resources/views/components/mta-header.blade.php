<nav class="mta-header-nav print:hidden">
    <a href="https://multitheftauto.com" rel="noopener">Home</a>
    <a class="keepMobile" href="https://discord.com/invite/mtasa" rel="noopener" target="_blank">Discord</a>
    <a href="https://community.multitheftauto.com" rel="noopener" class="currentSite">Community</a>
    <a href="https://forum.multitheftauto.com" rel="noopener">Forum</a>
    <a class="keepMobile" href="https://wiki.multitheftauto.com/wiki/Main_Page" rel="noopener">Wiki</a>
    <a class="keepMobile" href="https://github.com/multitheftauto/mtasa-blue/issues" rel="noopener" target="_blank">Bugs</a>
    <a href="https://multitheftauto.com/donate/" rel="noopener">Heroes</a>
    <a href="https://multitheftauto.com/hosters/" rel="noopener">Hosting</a>
    <a href="https://community.multitheftauto.com/index.php?p=servers">Servers</a>
    <a href="https://streamlabs.com/mtaqa/merch" rel="noopener" target="_blank">Merch</a>
    <a href="https://multitheftauto.crowdin.com/multitheftauto" rel="noopener" target="_blank">Crowdin</a>
    <span id="onlinePlayers"></span>
    <script>
        function updateOnlinePlayers() {
            var onlinePlayersEl = document.getElementById("onlinePlayers");
            if (onlinePlayersEl) {
                fetch("https://multitheftauto.com/count/")
                    .then(function(r) {
                        return r.text();
                    })
                    .then(function(r) {
                        var info = r.split(",");
                        if (info[0] && info[1]) {
                            onlinePlayersEl.innerHTML = new Intl.NumberFormat("en-US").format(info[0]) + " players online on " + new Intl.NumberFormat("en-US").format(info[1]) + " public servers";
                        }
                    });
            }
        }
        updateOnlinePlayers();
        setInterval(updateOnlinePlayers, 300000);
    </script>
</nav>

