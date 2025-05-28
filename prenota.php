<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['evento_id']) || !is_numeric($_GET['evento_id'])) {
    header("Location: visite.php");
    exit();
}

$evento_id = (int)$_GET['evento_id'];

$config = require('databaseConfig.php');
require_once('DBcon.php');
$db = DBcon::getDB($config);

$page_title = "Prenota Visita | Artifex";
$current_page = "prenota";

// Recupera dettagli evento
$evento = [];
try {
    $stmt = $db->prepare("
        SELECT e.*, v.titolo, v.luogo, v.durata, v.img,
               g.nome AS guida_nome, g.cognome AS guida_cognome
        FROM Evento e
        JOIN Evento_Visita ev ON e.id = ev.id_evento
        JOIN Visita v ON ev.visita = v.titolo
        JOIN Guida g ON e.guida = g.id
        WHERE e.id = :evento_id
    ");
    $stmt->execute(['evento_id' => $evento_id]);
    $evento = $stmt->fetch();

    if (!$evento) {
        header("Location: visite.php");
        exit();
    }
} catch(PDOException $e) {
    $error = "Errore nel recupero dei dettagli dell'evento";
}

// Gestione prenotazione
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verifica se esiste già una prenotazione
        $stmt = $db->prepare("
            SELECT * FROM Evento_prenotato 
            WHERE utente = :utente AND id_evento = :evento_id
        ");
        $stmt->execute([
            'utente' => $_SESSION['user_id'],
            'evento_id' => $evento_id
        ]);

        if ($stmt->rowCount() > 0) {
            $error = "Hai già prenotato questo evento";
        } else {
            // Crea nuova prenotazione
            $stmt = $db->prepare("
                INSERT INTO Evento_prenotato (id_evento, utente, stato)
                VALUES (:evento_id, :utente, 'prenotato')
            ");
            $stmt->execute([
                'evento_id' => $evento_id,
                'utente' => $_SESSION['user_id']
            ]);

            $success = "Prenotazione effettuata con successo!";
        }
    } catch(PDOException $e) {
        $error = "Errore durante la prenotazione: " . $e->getMessage();
    }
}

include 'header.php';
?>

    <div class="booking-wrapper">
        <h1>Conferma Prenotazione</h1>

        <?php if (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="booking-details">
            <div class="booking-image">
                <img src="<?php echo htmlspecialchars($evento['img']); ?>" alt="<?php echo htmlspecialchars($evento['titolo']); ?>">
            </div>
            <div class="booking-info">
                <h2><?php echo htmlspecialchars($evento['titolo']); ?></h2>
                <p><strong>Luogo:</strong> <?php echo htmlspecialchars($evento['luogo']); ?></p>
                <p><strong>Durata:</strong> <?php echo htmlspecialchars($evento['durata']); ?> minuti</p>
                <p><strong>Lingua:</strong> <?php echo htmlspecialchars($evento['lingua']); ?></p>
                <p><strong>Guida:</strong> <?php echo htmlspecialchars($evento['guida_nome'] . ' ' . $evento['guida_cognome']); ?></p>
                <p><strong>Prezzo:</strong> €<?php echo number_format($evento['prezzo'], 2); ?></p>

                <?php if (empty($success)): ?>
                    <form method="POST" class="booking-form">
                        <button type="submit" class="btn btn-confirm">Conferma Prenotazione</button>
                        <a href="visite.php" class="btn btn-cancel">Annulla</a>
                    </form>
                <?php else: ?>
                    <div class="booking-actions">
                        <a href="carrello.php" class="btn btn-profile">Vai al tuo carrello</a>
                        <a href="visite.php" class="btn btn-more">Prenota un altro evento</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>


<?php include 'footer.php'; ?>