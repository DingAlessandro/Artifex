<?php
// session & controllo admin
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'amministratore') {
    header("Location: login.php");
    exit();
}

$page_title = "Gestione Utenti | Artifex";
$current_page = "admin_utenti";

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
                $username = $_POST['username'] ?? '';
                $pwd = $_POST['pwd'] ?? '';
                $nome = $_POST['nome'] ?? '';
                $email = $_POST['email'] ?? '';
                $nazionalita = $_POST['nazionalita'] ?? null;
                $telefono = $_POST['telefono'] ?? null;
                $lingua = $_POST['lingua'] ?? null;
                $tipo = $_POST['tipo'] ?? 'turista';

                // Validazione semplice (puoi estendere)
                if ($username && $pwd && $nome && $email && in_array($tipo, ['turista', 'amministratore'])) {
                    // Puoi aggiungere hash pwd qui (consigliato)
                    $stmt = $db->prepare("INSERT INTO Utente (username, pwd, nome, email, nazionalita, telefono, lingua, tipo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$username, $pwd, $nome, $email, $nazionalita, $telefono, $lingua, $tipo]);
                    $success_message = "Utente creato con successo!";
                } else {
                    $error_message = "Compila tutti i campi obbligatori correttamente.";
                }
                break;

            case 'update':
                $username = $_POST['username'] ?? '';
                $pwd = $_POST['pwd'] ?? null; // opzionale update pwd
                $nome = $_POST['nome'] ?? '';
                $email = $_POST['email'] ?? '';
                $nazionalita = $_POST['nazionalita'] ?? null;
                $telefono = $_POST['telefono'] ?? null;
                $lingua = $_POST['lingua'] ?? null;
                $tipo = $_POST['tipo'] ?? 'turista';

                if ($username && $nome && $email && in_array($tipo, ['turista', 'amministratore'])) {
                    if ($pwd) {
                        $stmt = $db->prepare("UPDATE Utente SET pwd = ?, nome = ?, email = ?, nazionalita = ?, telefono = ?, lingua = ?, tipo = ? WHERE username = ?");
                        $stmt->execute([$pwd, $nome, $email, $nazionalita, $telefono, $lingua, $tipo, $username]);
                    } else {
                        $stmt = $db->prepare("UPDATE Utente SET nome = ?, email = ?, nazionalita = ?, telefono = ?, lingua = ?, tipo = ? WHERE username = ?");
                        $stmt->execute([$nome, $email, $nazionalita, $telefono, $lingua, $tipo, $username]);
                    }
                    $success_message = "Utente aggiornato con successo!";
                } else {
                    $error_message = "Compila tutti i campi obbligatori correttamente.";
                }
                break;

            case 'delete':
                $username = $_POST['username'] ?? '';
                if ($username) {
                    $stmt = $db->prepare("DELETE FROM Utente WHERE username = ?");
                    $stmt->execute([$username]);
                    $success_message = "Utente eliminato con successo!";
                } else {
                    $error_message = "Username non valido.";
                }
                break;

            default:
                $error_message = "Azione non riconosciuta.";
        }
    }
} catch (PDOException $e) {
    $error_message = "Errore durante l'operazione: " . $e->getMessage();
}

// Recupero utenti
try {
    $stmt = $db->query("SELECT username, nome, email, nazionalita, telefono, lingua, tipo FROM Utente ORDER BY username");
    $utenti = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Errore nel recupero degli utenti: " . $e->getMessage();
}

// Preparazione modifica
$edit_mode = isset($_GET['action']) && in_array($_GET['action'], ['edit', 'create']);
$utente_to_edit = null;
if (isset($_GET['action'], $_GET['username']) && $_GET['action'] === 'edit') {
    try {
        $stmt = $db->prepare("SELECT username, nome, email, nazionalita, telefono, lingua, tipo FROM Utente WHERE username = ?");
        $stmt->execute([$_GET['username']]);
        $utente_to_edit = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error_message = "Errore nel recupero dell'utente da modificare: " . $e->getMessage();
    }
}

// Recupero lingue per select
try {
    $stmt = $db->query("SELECT lingua FROM Lingua ORDER BY lingua");
    $lingue = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $lingue = [];
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
            <li class="active"><a href="admin_utenti.php"><i class="fas fa-users"></i> Utenti</a></li>
            <li><a href="admin_lingue.php"><i class="fas fa-language"></i> Lingue</a></li>
            <li><a href="admin_prenotazioni.php"><i class="fas fa-ticket-alt"></i> Prenotazioni</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="page-header">
            <h1>Gestione Utenti</h1>
        </div>

        <?php if ($success_message): ?>
            <div class="alert success"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert error"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <?php if ($edit_mode): ?>
            <div class="edit-form-container">
                <h2><?= $_GET['action'] === 'create' ? 'Crea Nuovo Utente' : 'Modifica Utente' ?></h2>
                <form method="POST" class="edit-form">
                    <input type="hidden" name="action" value="<?= $_GET['action'] === 'create' ? 'create' : 'update' ?>">
                    <?php if ($_GET['action'] === 'edit'): ?>
                        <input type="hidden" name="username" value="<?= htmlspecialchars($utente_to_edit['username']) ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" name="username" id="username" required
                            <?= $_GET['action'] === 'edit' ? 'readonly' : '' ?>
                               value="<?= htmlspecialchars($utente_to_edit['username'] ?? '') ?>"
                               class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="pwd"><?= $_GET['action'] === 'edit' ? 'Nuova Password (lascia vuoto per non cambiare):' : 'Password:' ?></label>
                        <input type="password" name="pwd" id="pwd" <?= $_GET['action'] === 'create' ? 'required' : '' ?> class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="nome">Nome:</label>
                        <input type="text" name="nome" id="nome" required
                               value="<?= htmlspecialchars($utente_to_edit['nome'] ?? '') ?>" class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" name="email" id="email" required
                               value="<?= htmlspecialchars($utente_to_edit['email'] ?? '') ?>" class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="nazionalita">Nazionalità:</label>
                        <input type="text" name="nazionalita" id="nazionalita"
                               value="<?= htmlspecialchars($utente_to_edit['nazionalita'] ?? '') ?>" class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="telefono">Telefono:</label>
                        <input type="text" name="telefono" id="telefono"
                               value="<?= htmlspecialchars($utente_to_edit['telefono'] ?? '') ?>" class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="lingua">Lingua:</label>
                        <select name="lingua" id="lingua" class="form-control">
                            <option value="">-- Seleziona lingua --</option>
                            <?php foreach ($lingue as $lang): ?>
                                <option value="<?= htmlspecialchars($lang) ?>"
                                    <?= (isset($utente_to_edit['lingua']) && $utente_to_edit['lingua'] === $lang) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($lang) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="tipo">Tipo:</label>
                        <select name="tipo" id="tipo" required class="form-control">
                            <option value="turista" <?= (isset($utente_to_edit['tipo']) && $utente_to_edit['tipo'] === 'turista') ? 'selected' : '' ?>>Turista</option>
                            <option value="amministratore" <?= (isset($utente_to_edit['tipo']) && $utente_to_edit['tipo'] === 'amministratore') ? 'selected' : '' ?>>Amministratore</option>
                        </select>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn">Salva</button>
                        <a href="admin_utenti.php" class="btn secondary">Annulla</a>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="actions-bar">
                <a href="admin_utenti.php?action=create" class="btn"><i class="fas fa-plus"></i> Nuovo Utente</a>
            </div>

            <div class="data-table-container">
                <table class="data-table">
                    <thead>
                    <tr>
                        <th>Username</th>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Nazionalità</th>
                        <th>Telefono</th>
                        <th>Lingua</th>
                        <th>Tipo</th>
                        <th>Azioni</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (count($utenti) > 0): ?>
                        <?php foreach ($utenti as $utente): ?>
                            <tr>
                                <td><?= htmlspecialchars($utente['username']) ?></td>
                                <td><?= htmlspecialchars($utente['nome']) ?></td>
                                <td><?= htmlspecialchars($utente['email']) ?></td>
                                <td><?= htmlspecialchars($utente['nazionalita']) ?></td>
                                <td><?= htmlspecialchars($utente['telefono']) ?></td>
                                <td><?= htmlspecialchars($utente['lingua']) ?></td>
                                <td><?= htmlspecialchars($utente['tipo']) ?></td>
                                <td class="actions">
                                    <a href="admin_utenti.php?action=edit&username=<?= urlencode($utente['username']) ?>" class="btn btn-small">
                                        <i class="fas fa-edit"></i> Modifica
                                    </a>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Sei sicuro di voler eliminare questo utente?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="username" value="<?= htmlspecialchars($utente['username']) ?>">
                                        <button type="submit" class="btn btn-small secondary"><i class="fas fa-trash"></i> Elimina</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="no-data">Nessun utente trovato</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
