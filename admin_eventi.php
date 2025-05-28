<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'amministratore') {
    header("Location: login.php");
    exit();
}

$page_title = "Gestione Eventi | Artifex";
$current_page = "admin_eventi";

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
                $lingua = $_POST['lingua'] ?? '';
                $prezzo = $_POST['prezzo'] ?? '';
                $guida = $_POST['guida'] ?? '';
                $visita = $_POST['visita'] ?? '';

                if ($lingua && $prezzo && $guida && $visita) {
                    $db->beginTransaction();

                    $stmt = $db->prepare("INSERT INTO Evento (lingua, prezzo, guida) VALUES (?, ?, ?)");
                    $stmt->execute([$lingua, $prezzo, $guida]);
                    $evento_id = $db->lastInsertId();

                    $stmt = $db->prepare("INSERT INTO Evento_Visita (visita, id_evento) VALUES (?, ?)");
                    $stmt->execute([$visita, $evento_id]);

                    $db->commit();
                    $success_message = "Evento creato con successo!";
                } else {
                    $error_message = "Tutti i campi sono obbligatori.";
                }
                break;

            case 'update':
                $id = $_POST['id'] ?? '';
                $lingua = $_POST['lingua'] ?? '';
                $prezzo = $_POST['prezzo'] ?? '';
                $guida = $_POST['guida'] ?? '';
                $visita = $_POST['visita'] ?? '';

                if ($id && $lingua && $prezzo && $guida && $visita) {
                    $db->beginTransaction();

                    $stmt = $db->prepare("UPDATE Evento SET lingua = ?, prezzo = ?, guida = ? WHERE id = ?");
                    $stmt->execute([$lingua, $prezzo, $guida, $id]);

                    $stmt = $db->prepare("UPDATE Evento_Visita SET visita = ? WHERE id_evento = ?");
                    $stmt->execute([$visita, $id]);

                    $db->commit();
                    $success_message = "Evento aggiornato con successo!";
                } else {
                    $error_message = "Tutti i campi sono obbligatori.";
                }
                break;

            case 'delete':
                $id = $_POST['id'] ?? '';

                if ($id) {
                    $db->beginTransaction();

                    $stmt = $db->prepare("DELETE FROM Evento_Visita WHERE id_evento = ?");
                    $stmt->execute([$id]);

                    $stmt = $db->prepare("DELETE FROM Evento_prenotato WHERE id_evento = ?");
                    $stmt->execute([$id]);

                    $stmt = $db->prepare("DELETE FROM Evento WHERE id = ?");
                    $stmt->execute([$id]);

                    $db->commit();
                    $success_message = "Evento eliminato con successo!";
                } else {
                    $error_message = "ID evento non valido.";
                }
                break;

            default:
                $error_message = "Azione non riconosciuta.";
        }
    }
} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    $error_message = "Errore durante l'operazione: " . $e->getMessage();
}

// Recupero eventi
try {
    $stmt = $db->query("
        SELECT e.id, e.lingua, e.prezzo, e.guida, 
               CONCAT(g.nome, ' ', g.cognome) as guida_nome,
               ev.visita, v.durata, v.luogo
        FROM Evento e
        JOIN Guida g ON e.guida = g.id
        JOIN Evento_Visita ev ON e.id = ev.id_evento
        JOIN Visita v ON ev.visita = v.titolo
        ORDER BY e.id DESC
    ");
    $eventi = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Errore nel recupero degli eventi: " . $e->getMessage();
}

// Recupero lingue per guida
try {
    $stmt = $db->query("
    SELECT g.id AS guida_id, l.lingua
    FROM Guida g
    JOIN Lingua_Guida lg ON g.id = lg.id_guida
    JOIN Lingua l ON lg.lingua = l.lingua
");
    $guida_lingue_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $guida_lingue = [];
    foreach ($guida_lingue_raw as $row) {
        $guida_lingue[$row['guida_id']][] = $row['lingua'];
    }
} catch (PDOException $e) {
    $error_message = "Errore nel recupero delle lingue delle guide: " . $e->getMessage();
}

// Recupero guide
try {
    $stmt = $db->query("SELECT id, CONCAT(nome, ' ', cognome) as nome_completo FROM Guida");
    $guide = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Errore nel recupero delle guide: " . $e->getMessage();
}

// Recupero visite
try {
    $stmt = $db->query("SELECT titolo FROM Visita");
    $visite = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
} catch (PDOException $e) {
    $error_message = "Errore nel recupero delle visite: " . $e->getMessage();
}

// Preparazione per modifica evento
$edit_mode = isset($_GET['action']) && in_array($_GET['action'], ['edit', 'create']);
$evento_to_edit = null;

if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    try {
        $stmt = $db->prepare("
            SELECT e.id, e.lingua, e.prezzo, e.guida, ev.visita
            FROM Evento e
            JOIN Evento_Visita ev ON e.id = ev.id_evento
            WHERE e.id = ?
        ");
        $stmt->execute([$_GET['id']]);
        $evento_to_edit = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error_message = "Errore nel recupero dell'evento da modificare: " . $e->getMessage();
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
        <div class="admin-info">
            <div class="admin-avatar"><i class="fas fa-user-shield"></i></div>
            <div class="admin-details">
                <h3><?= htmlspecialchars($_SESSION['user_name']) ?></h3>
                <p>Amministratore</p>
            </div>
        </div>
        <ul class="admin-menu">
            <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li class="active"><a href="admin_eventi.php"><i class="fas fa-calendar-alt"></i> Eventi</a></li>
            <li><a href="admin_guide.php"><i class="fas fa-user-tie"></i> Guide</a></li>
            <li><a href="admin_visite.php"><i class="fas fa-monument"></i> Visite</a></li>
            <li><a href="admin_utenti.php"><i class="fas fa-users"></i> Utenti</a></li>
            <li><a href="admin_lingue.php"><i class="fas fa-language"></i> Lingue</a></li>
            <li><a href="admin_prenotazioni.php"><i class="fas fa-ticket-alt"></i> Prenotazioni</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="page-header">
            <h1>Gestione Eventi</h1>
        </div>

        <?php if ($success_message): ?>
            <div class="alert success"><?= $success_message ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert error"><?= $error_message ?></div>
        <?php endif; ?>

        <?php if ($edit_mode): ?>
            <div class="edit-form-container">
                <h2 class="form-title"><?= $_GET['action'] === 'create' ? 'Crea Nuovo Evento' : 'Modifica Evento' ?></h2>
                <form method="POST" class="edit-form" id="evento-form">
                    <input type="hidden" name="action" value="<?= $_GET['action'] === 'create' ? 'create' : 'update' ?>">
                    <?php if ($_GET['action'] === 'edit'): ?>
                        <input type="hidden" name="id" value="<?= $evento_to_edit['id'] ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="guida">Guida:</label>
                        <select id="guida" name="guida" required class="input-select" onchange="aggiornaLingue()">
                            <?php foreach ($guide as $guida): ?>
                                <option value="<?= $guida['id'] ?>" <?= isset($evento_to_edit) && $evento_to_edit['guida'] == $guida['id'] ? 'selected' : '' ?>>
                                    <?= $guida['nome_completo'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="lingua">Lingua:</label>
                        <select id="lingua" name="lingua" required class="input-select"></select>
                    </div>

                    <div class="form-group">
                        <label for="prezzo">Prezzo (€):</label>
                        <input type="number" name="prezzo" id="prezzo" step="0.01" min="0" required class="input-text"
                               value="<?= $evento_to_edit['prezzo'] ?? '' ?>">
                    </div>

                    <div class="form-group">
                        <label for="visita">Visita:</label>
                        <select id="visita" name="visita" required class="input-select">
                            <?php foreach ($visite as $visita): ?>
                                <option value="<?= $visita ?>" <?= isset($evento_to_edit) && $evento_to_edit['visita'] === $visita ? 'selected' : '' ?>>
                                    <?= $visita ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Salva</button>
                        <a href="admin_eventi.php" class="btn btn-secondary">Annulla</a>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="actions-bar">
                <a href="admin_eventi.php?action=create" class="btn btn-primary"><i class="fas fa-plus"></i> Nuovo Evento</a>
            </div>
            <div class="data-table-container">
                <table class="data-table" id="eventi-table">
                    <thead>
                    <tr>
                        <th class="col-id">ID</th>
                        <th class="col-lingua">Lingua</th>
                        <th class="col-prezzo">Prezzo</th>
                        <th class="col-guida">Guida</th>
                        <th class="col-visita">Visita</th>
                        <th class="col-durata">Durata</th>
                        <th class="col-luogo">Luogo</th>
                        <th class="col-azioni">Azioni</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (count($eventi) > 0): ?>
                        <?php foreach ($eventi as $evento): ?>
                            <tr class="evento-row">
                                <td class="cell-id"><?= $evento['id'] ?></td>
                                <td class="cell-lingua"><?= htmlspecialchars($evento['lingua']) ?></td>
                                <td class="cell-prezzo">€<?= number_format($evento['prezzo'], 2) ?></td>
                                <td class="cell-guida"><?= htmlspecialchars($evento['guida_nome']) ?></td>
                                <td class="cell-visita"><?= htmlspecialchars($evento['visita']) ?></td>
                                <td class="cell-durata"><?= $evento['durata'] ?> min</td>
                                <td class="cell-luogo"><?= htmlspecialchars($evento['luogo']) ?></td>
                                <td class="cell-azioni actions">
                                    <a href="admin_eventi.php?action=edit&id=<?= $evento['id'] ?>" class="btn btn-small btn-edit"><i class="fas fa-edit"></i> Modifica</a>
                                    <form method="POST" class="delete-form" style="display:inline;" onsubmit="return confirm('Sei sicuro?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $evento['id'] ?>">
                                        <button type="submit" class="btn btn-small btn-danger"><i class="fas fa-trash"></i> Elimina</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="no-data">Nessun evento trovato</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Script JS -->
<script>
    const guidaLingueMap = <?= json_encode($guida_lingue) ?>;

    function aggiornaLingue() {
        const guidaSelect = document.getElementById("guida");
        const linguaSelect = document.getElementById("lingua");
        const guidaId = guidaSelect.value;
        const lingue = guidaLingueMap[guidaId] || [];

        linguaSelect.innerHTML = "";

        lingue.forEach(lingua => {
            const option = document.createElement("option");
            option.value = lingua;
            option.textContent = lingua;
            linguaSelect.appendChild(option);
        });

        <?php if (isset($evento_to_edit)) : ?>
        linguaSelect.value = "<?= $evento_to_edit['lingua'] ?>";
        <?php endif; ?>
    }

    document.addEventListener("DOMContentLoaded", function () {
        if (document.getElementById("guida")) {
            aggiornaLingue();
        }
    });
</script>
</body>
</html>