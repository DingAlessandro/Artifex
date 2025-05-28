<?php
// session & controllo admin
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'amministratore') {
    header("Location: login.php");
    exit();
}

$page_title = "Gestione Lingue | Artifex";
$current_page = "admin_lingue";

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
            case 'create':
                $lingua = trim($_POST['lingua'] ?? '');

                if ($lingua !== '') {
                    // Controllo che lingua non esista già
                    $stmt = $db->prepare("SELECT COUNT(*) FROM Lingua WHERE lingua = ?");
                    $stmt->execute([$lingua]);
                    if ($stmt->fetchColumn() == 0) {
                        $stmt = $db->prepare("INSERT INTO Lingua (lingua) VALUES (?)");
                        $stmt->execute([$lingua]);
                        $success_message = "Lingua aggiunta con successo!";
                    } else {
                        $error_message = "La lingua esiste già.";
                    }
                } else {
                    $error_message = "Il campo lingua è obbligatorio.";
                }
                break;

            case 'update':
                $old_lingua = $_POST['old_lingua'] ?? '';
                $new_lingua = trim($_POST['lingua'] ?? '');

                if ($old_lingua !== '' && $new_lingua !== '') {
                    // Controllo che nuova lingua non esista già (escluso old)
                    $stmt = $db->prepare("SELECT COUNT(*) FROM Lingua WHERE lingua = ? AND lingua <> ?");
                    $stmt->execute([$new_lingua, $old_lingua]);
                    if ($stmt->fetchColumn() == 0) {
                        $stmt = $db->prepare("UPDATE Lingua SET lingua = ? WHERE lingua = ?");
                        $stmt->execute([$new_lingua, $old_lingua]);
                        $success_message = "Lingua aggiornata con successo!";
                    } else {
                        $error_message = "La nuova lingua esiste già.";
                    }
                } else {
                    $error_message = "Compila correttamente il campo lingua.";
                }
                break;

            case 'delete':
                $lingua = $_POST['lingua'] ?? '';
                if ($lingua !== '') {
                    // Opzionale: potresti voler verificare se la lingua è usata da altri record
                    $stmt = $db->prepare("DELETE FROM Lingua WHERE lingua = ?");
                    $stmt->execute([$lingua]);
                    $success_message = "Lingua eliminata con successo!";
                } else {
                    $error_message = "Lingua non valida.";
                }
                break;

            default:
                $error_message = "Azione non riconosciuta.";
        }
    }
} catch (PDOException $e) {
    $error_message = "Errore durante l'operazione: " . $e->getMessage();
}

// Recupero lingue
try {
    $stmt = $db->query("SELECT lingua FROM Lingua ORDER BY lingua");
    $lingue = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $error_message = "Errore nel recupero delle lingue: " . $e->getMessage();
}

// Preparazione modifica
$edit_mode = isset($_GET['action']) && in_array($_GET['action'], ['edit', 'create']);
$lingua_to_edit = null;
if (isset($_GET['action'], $_GET['lingua']) && $_GET['action'] === 'edit') {
    $lingua_sel = $_GET['lingua'];
    try {
        $stmt = $db->prepare("SELECT lingua FROM Lingua WHERE lingua = ?");
        $stmt->execute([$lingua_sel]);
        $lingua_to_edit = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error_message = "Errore nel recupero della lingua da modificare: " . $e->getMessage();
    }
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
        <!-- Copia la sidebar di admin_guide.php -->
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
            <li class="active"><a href="admin_lingue.php"><i class="fas fa-language"></i> Lingue</a></li>
            <li><a href="admin_prenotazioni.php"><i class="fas fa-ticket-alt"></i> Prenotazioni</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="page-header">
            <h1>Gestione Lingue</h1>
        </div>

        <?php if ($success_message): ?>
            <div class="alert success"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert error"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <?php if ($edit_mode): ?>
            <div class="edit-form-container">
                <h2><?= $_GET['action'] === 'create' ? 'Aggiungi Nuova Lingua' : 'Modifica Lingua' ?></h2>
                <form method="POST" class="edit-form">
                    <input type="hidden" name="action" value="<?= $_GET['action'] === 'create' ? 'create' : 'update' ?>">
                    <?php if ($_GET['action'] === 'edit'): ?>
                        <input type="hidden" name="old_lingua" value="<?= htmlspecialchars($lingua_to_edit['lingua']) ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="lingua">Lingua:</label>
                        <input type="text" name="lingua" id="lingua" required
                               value="<?= htmlspecialchars($lingua_to_edit['lingua'] ?? '') ?>"
                            <?= $_GET['action'] === 'edit' ? '' : '' ?> class="form-control">
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn">Salva</button>
                        <a href="admin_lingue.php" class="btn secondary">Annulla</a>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="actions-bar">
                <a href="admin_lingue.php?action=create" class="btn"><i class="fas fa-plus"></i> Nuova Lingua</a>
            </div>

            <div class="data-table-container">
                <table class="data-table">
                    <thead>
                    <tr>
                        <th>Lingua</th>
                        <th>Azioni</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (count($lingue) > 0): ?>
                        <?php foreach ($lingue as $ling): ?>
                            <tr>
                                <td><?= htmlspecialchars($ling) ?></td>
                                <td class="actions">
                                    <a href="admin_lingue.php?action=edit&lingua=<?= urlencode($ling) ?>" class="btn btn-small">
                                        <i class="fas fa-edit"></i> Modifica
                                    </a>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Sei sicuro di voler eliminare questa lingua?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="lingua" value="<?= htmlspecialchars($ling) ?>">
                                        <button type="submit" class="btn btn-small secondary"><i class="fas fa-trash"></i> Elimina</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="2" class="no-data">Nessuna lingua trovata</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
