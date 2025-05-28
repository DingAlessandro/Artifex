<?php
// session & controllo admin
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'amministratore') {
    header("Location: login.php");
    exit();
}

$page_title = "Gestione Prenotazioni | Artifex";
$current_page = "admin_prenotazioni";

$config = require('databaseConfig.php');
require_once('DBcon.php');
require_once('functions.php');
$db = DBcon::getDB($config);

$success_message = '';
$error_message = '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        $action = $_POST['action'];

        switch ($action) {
            case 'update_status':
                $id_evento = $_POST['id_evento'] ?? '';
                $utente = $_POST['utente'] ?? '';
                $stato = $_POST['stato'] ?? '';

                if ($id_evento !== '' && $utente !== '' && in_array($stato, ['prenotato', 'pagato'])) {
                    $stmt = $db->prepare("UPDATE Evento_prenotato SET stato = ? WHERE id_evento = ? AND utente = ?");
                    $stmt->execute([$stato, $id_evento, $utente]);
                    $success_message = "Stato prenotazione aggiornato con successo!";
                } else {
                    $error_message = "Dati mancanti o non validi per l'aggiornamento.";
                }
                break;

            case 'delete':
                $id_evento = $_POST['id_evento'] ?? '';
                $utente = $_POST['utente'] ?? '';

                if ($id_evento !== '' && $utente !== '') {
                    $stmt = $db->prepare("DELETE FROM Evento_prenotato WHERE id_evento = ? AND utente = ?");
                    $stmt->execute([$id_evento, $utente]);
                    $success_message = "Prenotazione eliminata con successo!";
                } else {
                    $error_message = "Dati mancanti per eliminare la prenotazione.";
                }
                break;

            default:
                $error_message = "Azione non riconosciuta.";
        }
    }
} catch (PDOException $e) {
    $error_message = "Errore durante l'operazione: " . $e->getMessage();
}

// Recupero prenotazioni con join per visualizzare info utili
try {
    $query = "
        SELECT ep.id_evento, ep.utente, ep.stato, 
               e.lingua, e.prezzo,
               u.nome AS nome_utente, u.email,
               g.nome AS nome_guida, g.cognome AS cognome_guida,
               GROUP_CONCAT(v.titolo SEPARATOR ', ') AS visite
        FROM Evento_prenotato ep
        JOIN Evento e ON ep.id_evento = e.id
        JOIN Utente u ON ep.utente = u.username
        JOIN Guida g ON e.guida = g.id
        LEFT JOIN Evento_Visita ev ON e.id = ev.id_evento
        LEFT JOIN Visita v ON ev.visita = v.titolo
        GROUP BY ep.id_evento, ep.utente, ep.stato, e.lingua, e.prezzo, u.nome, u.email, g.nome, g.cognome
        ORDER BY ep.id_evento DESC, ep.utente
    ";
    $stmt = $db->query($query);
    $prenotazioni = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Errore nel recupero delle prenotazioni: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($page_title) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
<div class="admin-dashboard">
    <div class="sidebar">
        <div class="admin-info">
            <div class="admin-avatar"><i class="fas fa-user-shield"></i></div>
            <div class="admin-details">
                <h3><?= htmlspecialchars($_SESSION['user_name']) ?></h3>
                <p>Amministratore</p>
            </div>
        </div>
        <ul class="admin-menu">
            <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="admin_eventi.php"><i class="fas fa-calendar-alt"></i> Eventi</a></li>
            <li><a href="admin_guide.php"><i class="fas fa-user-tie"></i> Guide</a></li>
            <li><a href="admin_visite.php"><i class="fas fa-monument"></i> Visite</a></li>
            <li><a href="admin_utenti.php"><i class="fas fa-users"></i> Utenti</a></li>
            <li><a href="admin_lingue.php"><i class="fas fa-language"></i> Lingue</a></li>
            <li class="active"><a href="admin_prenotazioni.php"><i class="fas fa-ticket-alt"></i> Prenotazioni</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="page-header">
            <h1>Gestione Prenotazioni</h1>
        </div>

        <?php if ($success_message): ?>
            <div class="alert success"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert error"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <div class="actions-bar">
            <p>Le prenotazioni possono essere modificate cambiando lo stato o eliminate.</p>
            <br>
        </div>

        <div class="data-table-container">
            <table class="data-table">
                <thead>
                <tr>
                    <th>ID Evento</th>
                    <th>Visite</th>
                    <th>Lingua</th>
                    <th>Prezzo (€)</th>
                    <th>Guida</th>
                    <th>Utente</th>
                    <th>Email</th>
                    <th>Stato</th>
                    <th>Azioni</th>
                </tr>
                </thead>
                <tbody>
                <?php if (count($prenotazioni) > 0): ?>
                    <?php foreach ($prenotazioni as $pren): ?>
                        <tr>
                            <td><?= htmlspecialchars($pren['id_evento']) ?></td>
                            <td><?= htmlspecialchars($pren['visite'] ?: '-') ?></td>
                            <td><?= htmlspecialchars($pren['lingua']) ?></td>
                            <td><?= number_format($pren['prezzo'], 2, ',', '.') ?></td>
                            <td><?= htmlspecialchars($pren['nome_guida'] . ' ' . $pren['cognome_guida']) ?></td>
                            <td><?= htmlspecialchars($pren['nome_utente']) ?></td>
                            <td><?= htmlspecialchars($pren['email']) ?></td>
                            <td>
                                <form method="POST" class="inline-form">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="id_evento" value="<?= htmlspecialchars($pren['id_evento']) ?>">
                                    <input type="hidden" name="utente" value="<?= htmlspecialchars($pren['utente']) ?>">
                                    <select name="stato" onchange="this.form.submit()" aria-label="Cambia stato prenotazione">
                                        <option value="prenotato" <?= $pren['stato'] === 'prenotato' ? 'selected' : '' ?>>Prenotato</option>
                                        <option value="pagato" <?= $pren['stato'] === 'pagato' ? 'selected' : '' ?>>Pagato</option>
                                    </select>
                                </form>
                            </td>
                            <td>
                                <form method="POST" onsubmit="return confirm('Sei sicuro di voler eliminare questa prenotazione?');" style="display:inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id_evento" value="<?= htmlspecialchars($pren['id_evento']) ?>">
                                    <input type="hidden" name="utente" value="<?= htmlspecialchars($pren['utente']) ?>">
                                    <button type="submit" class="btn btn-small secondary" title="Elimina prenotazione">
                                        <i class="fas fa-trash"></i> Elimina
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="9" class="no-data">Nessuna prenotazione trovata</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>
</body>
</html>
