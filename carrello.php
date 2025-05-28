<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$config = require('databaseConfig.php');
require_once('DBcon.php');
$db = DBcon::getDB($config);

$page_title = "Carrello | Artifex";
$current_page = "carrello";

// Recupera prenotazioni
$prenotazioni = [];
$totale = 0;
try {
    $stmt = $db->prepare("
        SELECT 
            e.id AS evento_id,
            e.prezzo,
            e.lingua,
            v.titolo AS visita_titolo,
            v.durata,
            v.luogo,
            v.img,
            g.nome AS guida_nome,
            g.cognome AS guida_cognome
        FROM Evento_prenotato p
        JOIN Evento e ON p.id_evento = e.id
        JOIN Evento_Visita ev ON e.id = ev.id_evento
        JOIN Visita v ON ev.visita = v.titolo
        JOIN Guida g ON e.guida = g.id
        WHERE p.utente = :user_id AND p.stato = 'prenotato'
    ");
    $stmt->execute(['user_id' => $_SESSION['user_id']]);
    $prenotazioni = $stmt->fetchAll();

    foreach ($prenotazioni as $prenotazione) {
        $totale += $prenotazione['prezzo'];
    }
} catch(PDOException $e) {
    $error = "Errore nel recupero delle prenotazioni: " . $e->getMessage();
}

// Annullamento prenotazione
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_event_id'])) {
    $eventIdToCancel = $_POST['cancel_event_id'];
    try {
        $stmt = $db->prepare("
            DELETE FROM Evento_prenotato 
            WHERE utente = :user_id 
              AND id_evento = :event_id 
              AND stato = 'prenotato'
        ");
        $stmt->execute([
            'user_id' => $_SESSION['user_id'],
            'event_id' => $eventIdToCancel
        ]);
        $success = "Prenotazione annullata con successo!";
        header("Location: carrello.php");
        exit();
    } catch (PDOException $e) {
        $error = "Errore durante l'annullamento: " . $e->getMessage();
    }
}

// Gestione pagamento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['card_number']) && !empty($prenotazioni)) {
    try {
        $db->beginTransaction();
        $stmt = $db->prepare("
            UPDATE Evento_prenotato 
            SET stato = 'pagato' 
            WHERE utente = :user_id AND stato = 'prenotato'
        ");
        $stmt->execute(['user_id' => $_SESSION['user_id']]);
        $db->commit();
        $success = "Pagamento effettuato con successo!";
        header("Location: carrello.php");
        exit();
    } catch(PDOException $e) {
        $db->rollBack();
        $error = "Errore durante il pagamento: " . $e->getMessage();
    }
}

include 'header.php';
?>

<div class="carrello-container">
    <h1>Il tuo Carrello</h1>

    <?php if (!empty($error)): ?>
        <div class="carrello-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="carrello-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <?php if (empty($prenotazioni)): ?>
        <div class="carrello-vuoto">
            <p>Il tuo carrello è vuoto</p>
            <a href="visite.php" class="btn btn-secondary">Sfoglia le visite disponibili</a>
        </div>
    <?php else: ?>
        <div class="carrello-items">
            <?php foreach ($prenotazioni as $prenotazione): ?>
                <div class="carrello-item">
                    <div class="item-img">
                        <img src="<?php echo htmlspecialchars($prenotazione['img']); ?>" alt="<?php echo htmlspecialchars($prenotazione['visita_titolo']); ?>">
                    </div>
                    <div class="item-info">
                        <h3><?php echo htmlspecialchars($prenotazione['visita_titolo']); ?></h3>
                        <p><strong>Luogo:</strong> <?php echo htmlspecialchars($prenotazione['luogo']); ?></p>
                        <p><strong>Durata:</strong> <?php echo htmlspecialchars($prenotazione['durata']); ?> minuti</p>
                        <p><strong>Lingua:</strong> <?php echo htmlspecialchars($prenotazione['lingua']); ?></p>
                        <p><strong>Guida:</strong> <?php echo htmlspecialchars($prenotazione['guida_nome'] . ' ' . $prenotazione['guida_cognome']); ?></p>
                        <p><strong>Prezzo:</strong> €<?php echo number_format($prenotazione['prezzo'], 2); ?></p>

                        <form method="POST" class="cancel-form" onsubmit="return confirm('Annullare questa prenotazione?');">
                            <input type="hidden" name="cancel_event_id" value="<?php echo $prenotazione['evento_id']; ?>">
                            <button type="submit" class="btn btn-cancel">Annulla</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="carrello-totale">
                <h3>Totale: €<?php echo number_format($totale, 2); ?></h3>
            </div>

            <form method="POST" class="carrello-pagamento-form">
                <div class="form-group">
                    <label for="card_number">Numero Carta</label>
                    <input type="text" id="card_number" name="card_number" required placeholder="1234 5678 9012 3456">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="expiry">Scadenza</label>
                        <input type="text" id="expiry" name="expiry" placeholder="MM/AA" required>
                    </div>
                    <div class="form-group">
                        <label for="cvv">CVV</label>
                        <input type="text" id="cvv" name="cvv" placeholder="123" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="card_name">Nome sulla Carta</label>
                    <input type="text" id="card_name" name="card_name" required>
                </div>
                <button type="submit" class="btn btn-pay">Completa il Pagamento</button>
                <a href="visite.php" class="btn btn-secondary">Continua a Prenotare</a>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
