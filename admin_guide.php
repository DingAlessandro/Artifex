<?php
// session & controllo admin come in admin_eventi.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'amministratore') {
    header("Location: login.php");
    exit();
}

$page_title = "Gestione Guide | Artifex";
$current_page = "admin_guide";

$config = require('databaseConfig.php');
require_once('DBcon.php');
require_once('functions.php');
$db = DBcon::getDB($config);

$success_message = '';
$error_message = '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        $action = $_POST['action'];

        // Azioni CRUD analoghe a admin_eventi.php ma per la tabella Guida
        switch ($action) {
            case 'create':
                $nome = $_POST['nome'] ?? '';
                $cognome = $_POST['cognome'] ?? '';
                // validazioni
                if ($nome && $cognome) {
                    $stmt = $db->prepare("INSERT INTO Guida (nome, cognome) VALUES (?, ?)");
                    $stmt->execute([$nome, $cognome]);
                    $success_message = "Guida creata con successo!";
                } else {
                    $error_message = "Tutti i campi sono obbligatori.";
                }
                break;
            case 'update':
                $id = $_POST['id'] ?? '';
                $nome = $_POST['nome'] ?? '';
                $cognome = $_POST['cognome'] ?? '';
                if ($id && $nome && $cognome) {
                    $stmt = $db->prepare("UPDATE Guida SET nome = ?, cognome = ? WHERE id = ?");
                    $stmt->execute([$nome, $cognome, $id]);
                    $success_message = "Guida aggiornata con successo!";
                } else {
                    $error_message = "Tutti i campi sono obbligatori.";
                }
                break;
            case 'delete':
                $id = $_POST['id'] ?? '';
                if ($id) {
                    $stmt = $db->prepare("DELETE FROM Guida WHERE id = ?");
                    $stmt->execute([$id]);
                    $success_message = "Guida eliminata con successo!";
                } else {
                    $error_message = "ID guida non valido.";
                }
                break;
            default:
                $error_message = "Azione non riconosciuta.";
        }
    }
} catch (PDOException $e) {
    $error_message = "Errore durante l'operazione: " . $e->getMessage();
}

// Recupero guide
try {
    $stmt = $db->query("SELECT id, nome, cognome FROM Guida ORDER BY id DESC");
    $guide = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Errore nel recupero delle guide: " . $e->getMessage();
}

// Preparazione modifica
$edit_mode = isset($_GET['action']) && in_array($_GET['action'], ['edit', 'create']);
$guida_to_edit = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    try {
        $stmt = $db->prepare("SELECT id, nome, cognome FROM Guida WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $guida_to_edit = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error_message = "Errore nel recupero della guida da modificare: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title><?= $page_title ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
<div class="admin-dashboard">
    <div class="sidebar">
        <!-- stessa sidebar identica ad admin_eventi.php -->
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
            <li class="active"><a href="admin_guide.php"><i class="fas fa-user-tie"></i> Guide</a></li>
            <li><a href="admin_visite.php"><i class="fas fa-monument"></i> Visite</a></li>
            <li><a href="admin_utenti.php"><i class="fas fa-users"></i> Utenti</a></li>
            <li><a href="admin_lingue.php"><i class="fas fa-language"></i> Lingue</a></li>
            <li><a href="admin_prenotazioni.php"><i class="fas fa-ticket-alt"></i> Prenotazioni</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="page-header">
            <h1>Gestione Guide</h1>
        </div>

        <?php if ($success_message): ?>
            <div class="alert success"><?= $success_message ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert error"><?= $error_message ?></div>
        <?php endif; ?>

        <?php if ($edit_mode): ?>
            <div class="edit-form-container">
                <h2><?= $_GET['action'] === 'create' ? 'Crea Nuova Guida' : 'Modifica Guida' ?></h2>
                <form method="POST" class="edit-form">
                    <input type="hidden" name="action" value="<?= $_GET['action'] === 'create' ? 'create' : 'update' ?>">
                    <?php if ($_GET['action'] === 'edit'): ?>
                        <input type="hidden" name="id" value="<?= $guida_to_edit['id'] ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="nome">Nome:</label>
                        <input type="text" name="nome" id="nome" required value="<?= htmlspecialchars($guida_to_edit['nome'] ?? '') ?>" class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="cognome">Cognome:</label>
                        <input type="text" name="cognome" id="cognome" required value="<?= htmlspecialchars($guida_to_edit['cognome'] ?? '') ?>" class="form-control">
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn">Salva</button>
                        <a href="admin_guide.php" class="btn secondary">Annulla</a>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="actions-bar">
                <a href="admin_guide.php?action=create" class="btn"><i class="fas fa-plus"></i> Nuova Guida</a>
            </div>

            <div class="data-table-container">
                <table class="data-table">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Cognome</th>
                        <th>Azioni</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (count($guide) > 0): ?>
                        <?php foreach ($guide as $guida): ?>
                            <tr>
                                <td><?= $guida['id'] ?></td>
                                <td><?= htmlspecialchars($guida['nome']) ?></td>
                                <td><?= htmlspecialchars($guida['cognome']) ?></td>
                                <td class="actions">
                                    <a href="admin_guide.php?action=edit&id=<?= $guida['id'] ?>" class="btn btn-small"><i class="fas fa-edit"></i> Modifica</a>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Sei sicuro?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $guida['id'] ?>">
                                        <button type="submit" class="btn btn-small secondary"><i class="fas fa-trash"></i> Elimina</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="no-data">Nessuna guida trovata</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
