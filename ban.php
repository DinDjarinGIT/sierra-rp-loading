<?php
// Configuration
$json_path = '/chemin/vers/garrysmod/data/ban_list.json'; // À MODIFIER

// Si le web est sur le même serveur que Gmod, exemple :
// $json_path = '/home/gmod/serverfiles/garrysmod/data/ban_list.json';

// Vérifie l'existence du fichier
if (!file_exists($json_path)) {
    die("Erreur: Fichier des bans introuvable. Vérifiez le chemin.");
}

// Charge les données
$json_content = file_get_contents($json_path);
$data = json_decode($json_content, true);

if (!$data) {
    die("Erreur: Impossible de lire le fichier JSON");
}

$bans = $data['bans'] ?? [];
$last_update = $data['last_update_formatted'] ?? 'Inconnue';
$total_bans = $data['total_bans'] ?? 0;

// Fonction pour formater la durée restante
function getTimeRemaining($end_time) {
    if ($end_time == 0) {
        return '<span class="badge badge-perma">PERMANENT</span>';
    }
    
    $remaining = $end_time - time();
    
    if ($remaining <= 0) {
        return '<span class="badge badge-expired">EXPIRÉ</span>';
    }
    
    $days = floor($remaining / 86400);
    $hours = floor(($remaining % 86400) / 3600);
    $minutes = floor(($remaining % 3600) / 60);
    
    if ($days > 0) {
        return sprintf('<span class="badge badge-temp">%dj %dh restant(es)</span>', $days, $hours);
    } elseif ($hours > 0) {
        return sprintf('<span class="badge badge-temp">%dh %dm restant(es)</span>', $hours, $minutes);
    } else {
        return sprintf('<span class="badge badge-temp">%d min restant(es)</span>', $minutes);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Bannis - Serveur DarkRP</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        header {
            background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        
        header h1 {
            font-size: 2.8em;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        header p {
            opacity: 0.9;
            font-size: 1.2em;
        }
        
        .stats {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-top: 20px;
        }
        
        .stat-box {
            background: rgba(255,255,255,0.1);
            padding: 15px 30px;
            border-radius: 10px;
            backdrop-filter: blur(10px);
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
        }
        
        .stat-label {
            font-size: 0.9em;
            opacity: 0.8;
            margin-top: 5px;
        }
        
        .search-bar {
            padding: 25px;
            background: #f7fafc;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .search-bar input {
            width: 100%;
            padding: 15px 20px;
            font-size: 16px;
            border: 2px solid #cbd5e0;
            border-radius: 10px;
            transition: all 0.3s;
        }
        
        .search-bar input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }
        
        .table-wrapper {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: #edf2f7;
            position: sticky;
            top: 0;
        }
        
        th {
            padding: 18px 15px;
            text-align: left;
            font-weight: 600;
            color: #2d3748;
            text-transform: uppercase;
            font-size: 0.85em;
            letter-spacing: 0.5px;
            border-bottom: 3px solid #cbd5e0;
        }
        
        tbody tr {
            border-bottom: 1px solid #e2e8f0;
            transition: all 0.2s;
        }
        
        tbody tr:hover {
            background: #f7fafc;
            transform: scale(1.01);
        }
        
        td {
            padding: 18px 15px;
            color: #4a5568;
        }
        
        .player-name {
            font-weight: 600;
            color: #2d3748;
            font-size: 1.05em;
        }
        
        .badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
            white-space: nowrap;
        }
        
        .badge-perma {
            background: #fed7d7;
            color: #c53030;
        }
        
        .badge-temp {
            background: #feebc8;
            color: #c05621;
        }
        
        .badge-expired {
            background: #e2e8f0;
            color: #718096;
        }
        
        .steam-id {
            font-family: 'Courier New', monospace;
            background: #edf2f7;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.9em;
            color: #4a5568;
        }
        
        .reason {
            max-width: 300px;
            word-wrap: break-word;
        }
        
        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: #718096;
            font-size: 1.3em;
        }
        
        .no-results::before {
            content: "🔍";
            font-size: 3em;
            display: block;
            margin-bottom: 20px;
        }
        
        footer {
            text-align: center;
            padding: 25px;
            color: #718096;
            background: #f7fafc;
            border-top: 2px solid #e2e8f0;
        }
        
        .update-time {
            font-weight: 600;
            color: #4a5568;
        }
        
        @media (max-width: 768px) {
            header h1 {
                font-size: 2em;
            }
            
            .stats {
                flex-direction: column;
                gap: 15px;
            }
            
            table {
                font-size: 0.9em;
            }
            
            th, td {
                padding: 12px 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>🚫 Liste des Bannis</h1>
            <p>Serveur DarkRP Serious RP</p>
            <div class="stats">
                <div class="stat-box">
                    <div class="stat-number"><?= $total_bans ?></div>
                    <div class="stat-label">Ban(s) actif(s)</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?= count(array_filter($bans, fn($b) => $b['is_permanent'])) ?></div>
                    <div class="stat-label">Permanent(s)</div>
                </div>
            </div>
        </header>
        
        <div class="search-bar">
            <input type="text" id="searchInput" placeholder="🔍 Rechercher par nom, SteamID, raison ou admin...">
        </div>
        
        <div class="table-wrapper">
            <table id="banTable">
                <thead>
                    <tr>
                        <th>Joueur</th>
                        <th>SteamID</th>
                        <th>Raison</th>
                        <th>Admin</th>
                        <th>Date du ban</th>
                        <th>Durée restante</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($bans)): ?>
                        <tr>
                            <td colspan="6" class="no-results">
                                Aucun ban actif ! 🎉
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($bans as $ban): ?>
                            <tr>
                                <td><span class="player-name"><?= htmlspecialchars($ban['name']) ?></span></td>
                                <td><span class="steam-id"><?= htmlspecialchars($ban['steam_id']) ?></span></td>
                                <td class="reason"><?= htmlspecialchars($ban['reason']) ?></td>
                                <td><?= htmlspecialchars($ban['admin_name']) ?></td>
                                <td><?= date('d/m/Y H:i', $ban['start_time']) ?></td>
                                <td><?= getTimeRemaining($ban['end_time']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <footer>
            <p>Dernière mise à jour : <span class="update-time"><?= $last_update ?></span></p>
            <p style="margin-top: 10px; font-size: 0.9em; opacity: 0.7;">
                Mise à jour automatique toutes les 5 minutes
            </p>
        </footer>
    </div>
    
    <script>
        // Recherche en temps réel
        const searchInput = document.getElementById('searchInput');
        const tableRows = document.querySelectorAll('#banTable tbody tr');
        
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            
            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
        
        // Auto-refresh toutes les 5 minutes
        setTimeout(() => {
            location.reload();
        }, 300000); // 5 minutes
    </script>
</body>
</html>
