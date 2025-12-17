// mta_stats/app.js
// Node.js app to periodically fetch MTA player/server counts,
// store history in SQLite, and expose it via Express.

/*
---- Run with pm2 ----

pm2 start app.js --name mta-count-cache
pm2 save
pm2 startup

The app will keep fetching periodically as long as pm2 keeps it alive.
*/

const express = require("express");
const fetch = require("node-fetch"); // npm i node-fetch@2
const sqlite3 = require("sqlite3").verbose();
const path = require("path");
const fs = require("fs");

// ---- Config ----
const FETCH_URL = "https://multitheftauto.com/count/";
const FETCH_INTERVAL_MS = 15 * 60 * 1000;
const DB_PATH = path.join(__dirname, "counts.sqlite");
const PORT = 3069;
const LAST_CHECK_FILE = path.join(__dirname, "last_check.txt");

// ---- SQLite setup ----
const db = new sqlite3.Database(DB_PATH);

db.serialize(() => {
    db.run(`
    CREATE TABLE IF NOT EXISTS counts (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      players INTEGER NOT NULL,
      servers INTEGER NOT NULL,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
  `);
});

// ---- Fetch + cache logic ----
async function fetchAndStoreCount() {
    try {
        const res = await fetch(FETCH_URL, { timeout: 10000 });
        if (!res.ok) return; // silent fail

        const text = (await res.text()).trim();

        // Expect format: "players,servers"
        const match = text.match(/^(\d+),(\d+)$/);
        if (!match) return; // silent fail

        const players = parseInt(match[1], 10);
        const servers = parseInt(match[2], 10);

        db.run("INSERT INTO counts (players, servers) VALUES (?, ?) ", [
            players,
            servers,
        ]);

        fs.writeFileSync(LAST_CHECK_FILE, String(Date.now()));
    } catch (err) {
        // Silent fail by design
    }
}

function getLastCheckTime() {
    try {
        const ts = fs.readFileSync(LAST_CHECK_FILE, "utf8").trim();
        const time = parseInt(ts, 10);
        return isNaN(time) ? null : time;
    } catch {
        return null; // file does not exist or unreadable
    }
}

// Initial fetch + interval
const lastCheck = getLastCheckTime();
const now = Date.now();

let initialDelay = 0;

if (lastCheck) {
    const elapsed = now - lastCheck;
    if (elapsed < FETCH_INTERVAL_MS) {
        initialDelay = FETCH_INTERVAL_MS - elapsed;
    }
}

console.log(`Initial delay: ${initialDelay / 1000} seconds`);
setTimeout(() => {
    fetchAndStoreCount();
    setInterval(fetchAndStoreCount, FETCH_INTERVAL_MS);
}, initialDelay);

// ---- Express API ----
const app = express();

// GET /history/:days
// Returns all entries from the last N days
app.get("/history/:days", (req, res) => {
    const days = parseInt(req.params.days, 10);
    if (isNaN(days) || days <= 0) {
        return res
            .status(400)
            .json({ error: "days must be a positive integer" });
    }

    db.all(
        `
      SELECT players, servers, created_at
      FROM counts
      WHERE created_at >= datetime('now', ?)
      ORDER BY created_at ASC
    `,
        [`-${days} days`],
        (err, rows) => {
            if (err) {
                return res.status(500).json({ error: "database error" });
            }
            res.json(rows);
        }
    );
});

app.listen(PORT, () => {
    console.log(`MTA count cache server running on port ${PORT}`);
});
