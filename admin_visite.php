<?php
// session & controllo admin
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'amministratore') {
    header("Location: login.php");
    exit();
}

$page_title = "Gestione Visite | Artifex";
$current_page = "admin_visite";

$config = require('databaseConfig.php');
require_once('DBcon.php');
require_once('functions.php');
$db = DBcon::getDB($config);

$success_message = '';
$error_message = '';

// Funzione di sanitizzazione input
function test_input($data) {
    return htmlspecialchars(trim($data));
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        $action = $_POST['action'];

        switch ($action) {
            case 'create':
                $titolo = $_POST['titolo'] ?? '';
                $durata = $_POST['durata'] ?? '';
                $luogo = $_POST['luogo'] ?? '';
                // Validazione semplice
                if ($titolo && $durata && $luogo) {
                    $titolo = test_input($titolo);
                    $durata = intval($durata);
                    $luogo = test_input($luogo);

                    // Upload immagine (opzionale, se non presente errore)
                    $img_path = upload_image('img');
                    if ($img_path === false) {
                        throw new Exception("Carica un'immagine valida (jpeg, png, gif).");
                    }

                    $stmt = $db->prepare("INSERT INTO Visita (titolo, durata, luogo, img) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$titolo, $durata, $luogo, $img_path]);
                    $success_message = "Visita creata con successo!";
                } else {
                    $error_message = "Tutti i campi sono obbligatori.";
                }
                break;

            case 'update':
                $titolo = $_POST['titolo'] ?? '';
                $durata = $_POST['durata'] ?? '';
                $luogo = $_POST['luogo'] ?? '';
                if ($titolo && $durata && $luogo) {
                    $titolo = test_input($titolo);
                    $durata = intval($durata);
                    $luogo = test_input($luogo);

                    // Controllo se è stata caricata nuova immagine
                    $img_path = upload_image('img');
                    if ($img_path !== false) {
                        // Aggiorno anche l'immagine
                        $stmt = $db->prepare("UPDATE Visita SET durata = ?, luogo = ?, img = ? WHERE titolo = ?");
                        $stmt->execute([$durata, $luogo, $img_path, $titolo]);
                    } else {
                        // Aggiorno senza modificare immagine
                        $stmt = $db->prepare("UPDATE Visita SET durata = ?, luogo = ? WHERE titolo = ?");
                        $stmt->execute([$durata, $luogo, $titolo]);
                    }
                    $success_message = "Visita aggiornata con successo!";
                } else {
                    $error_message = "Tutti i campi sono obbligatori.";
                }
                break;

            case 'delete':
                $titolo = $_POST['titolo'] ?? '';
                if ($titolo) {
                    $titolo = test_input($titolo);
                    $stmt = $db->prepare("DELETE FROM Visita WHERE titolo = ?");
                    $stmt->execute([$titolo]);
                    $success_message = "Visita eliminata con successo!";
                } else {
                    $error_message = "Titolo visita non valido.";
                }
                break;

            default:
                $error_message = "Azione non riconosciuta.";
        }
    }
} catch (Exception $e) {
    $error_message = "Errore durante l'operazione: " . $e->getMessage();
} catch (PDOException $e) {
    $error_message = "Errore database: " . $e->getMessage();
}

// Recupero visite
try {
    $stmt = $db->query("SELECT titolo, durata, luogo, img FROM Visita ORDER BY titolo DESC");
    $visite = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Errore nel recupero delle visite: " . $e->getMessage();
}

// Preparazione modifica o creazione
$edit_mode = isset($_GET['action']) && in_array($_GET['action'], ['edit', 'create']);
$visita_to_edit = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['titolo'])) {
    try {
        $stmt = $db->prepare("SELECT titolo, durata, luogo, img FROM Visita WHERE titolo = ?");
        $stmt->execute([$_GET['titolo']]);
        $visita_to_edit = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error_message = "Errore nel recupero della visita da modificare: " . $e->getMessage();
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
    <style>
        img.visita-img { max-width: 150px; height: auto; }
        form.edit-form { max-width: 600px; }
        form.edit-form .form-group { margin-bottom: 15px; }
        form.edit-form label { display: block; font-weight: bold; margin-bottom: 5px; }
        form.edit-form input[type="text"],
        form.edit-form input[type="number"],
        form.edit-form input[type="file"] { width: 100%; padding: 8px; box-sizing: border-box; }
        .form-actions { margin-top: 20px; }
        .form-actions .btn { margin-right: 10px; }
        table.data-table { width: 100%; border-collapse: collapse; }
        table.data-table th, table.data-table td { border: 1px solid #ccc; padding: 10px; text-align: left; }
        table.data-table th { background: #f4f4f4; }
        .actions form { display: inline; }
    </style>
</head>
<body>
<div class="admin-dashboard">
    <div class="sidebar">
        <!-- sidebar identica a quella di admin_eventi.php -->
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
            <li class="active"><a href="admin_visite.php"><i class="fas fa-monument"></i> Visite</a></li>
            <li><a href="admin_utenti.php"><i class="fas fa-users"></i> Utenti</a></li>
            <li><a href="admin_lingue.php"><i class="fas fa-language"></i> Lingue</a></li>
            <li><a href="admin_prenotazioni.php"><i class="fas fa-ticket-alt"></i> Prenotazioni</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="page-header">
            <h1>Gestione Visite</h1>
        </div>

        <?php if ($success_message): ?>
            <div class="alert success"><?= $success_message ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert error"><?= $error_message ?></div>
        <?php endif; ?>

        <?php if ($edit_mode): ?>
            <div class="edit-form-container">
                <h2><?= $_GET['action'] === 'create' ? 'Crea Nuova Visita' : 'Modifica Visita' ?></h2>
                <form method="POST" enctype="multipart/form-data" class="edit-form">
                    <input type="hidden" name="action" value="<?= $_GET['action'] === 'create' ? 'create' : 'update' ?>">
                    <?php if ($_GET['action'] === 'edit'): ?>
                        <input type="hidden" name="titolo" value="<?= htmlspecialchars($visita_to_edit['titolo']) ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="titolo">Titolo:</label>
                        <?php if ($_GET['action'] === 'create'): ?>
                            <input type="text" name="titolo" id="titolo" required value="">
                        <?php else: ?>
                            <input type="text" id="titolo" disabled value="<?= htmlspecialchars($visita_to_edit['titolo']) ?>">
                            <small><i>Il titolo non è modificabile.</i></small>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="durata">Durata (minuti):</label>
                        <input type="number" name="durata" id="durata" min="1" required value="<?= htmlspecialchars($visita_to_edit['durata'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="luogo">Luogo:</label>
                        <input type="text" name="luogo" id="luogo" required value="<?= htmlspecialchars($visita_to_edit['luogo'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="img">Immagine <?= $_GET['action'] === 'edit' ? '(lascia vuoto per mantenere attuale)' : '' ?>:</label>
                        <input type="file" name="img" id="img" accept="image/*" <?= $_GET['action'] === 'create' ? 'required' : '' ?>>
                        <?php if ($_GET['action'] === 'edit' && $visita_to_edit['img']): ?>
                            <br><img src="<?= htmlspecialchars($visita_to_edit['img']) ?>" alt="Immagine Visita" class="visita-img">
                        <?php endif; ?>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn"><?= $_GET['action'] === 'create' ? 'Crea' : 'Aggiorna' ?></button>
                        <a href="admin_visite.php" class="btn secondary">Annulla</a>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="actions-bar">
                <a href="admin_visite.php?action=create" class="btn"><i class="fas fa-plus"></i> Nuova Visita</a>
            </div>

            <div class="data-table-container">
                <table class="data-table">
                    <thead>
                    <tr>
                        <th>Titolo</th>
                        <th>Durata (min)</th>
                        <th>Luogo</th>
                        <th>Immagine</th>
                        <th>Azioni</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (count($visite) > 0): ?>
                        <?php foreach ($visite as $visita): ?>
                            <tr>
                                <td><?= htmlspecialchars($visita['titolo']) ?></td>
                                <td><?= htmlspecialchars($visita['durata']) ?></td>
                                <td><?= htmlspecialchars($visita['luogo']) ?></td>
                                <td>
                                    <?php if ($visita['img']): ?>
                                        <img src="<?= htmlspecialchars($visita['img']) ?>" alt="Immagine Visita" class="visita-img">
                                    <?php else: ?>
                                        Nessuna immagine
                                    <?php endif; ?>
                                </td>
                                <td class="actions">
                                    <a href="admin_visite.php?action=edit&titolo=<?= urlencode($visita['titolo']) ?>" class="btn btn-small"><i class="fas fa-edit"></i> Modifica</a>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Sei sicuro di voler eliminare questa visita?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="titolo" value="<?= htmlspecialchars($visita['titolo']) ?>">
                                        <button type="submit" class="btn btn-small secondary"><i class="fas fa-trash"></i> Elimina</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="no-data">Nessuna visita trovata</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Funzione di upload immagine (restituisce percorso o false)
function upload_image($input_name) {
    if (!isset($_FILES[$input_name]) || $_FILES[$input_name]['error'] === UPLOAD_ERR_NO_FILE) {
        return false; // Nessun file caricato
    }

    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $file = $_FILES[$input_name];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    if (!in_array(mime_content_type($file['tmp_name']), $allowed_types)) {
        return false;
    }

    $upload_dir = 'uploads/visite/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $filename = uniqid() . '_' . basename($file['name']);
    $target_path = $upload_dir . $filename;

    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        return $target_path;
    }

    return false;
}
?>
</body>
</html>
