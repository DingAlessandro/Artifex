<?php
// Secure session start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database configuration
$config = require('databaseConfig.php');
require_once('DBcon.php');
$db = DBcon::getDB($config);

$page_title = "Profilo | Artifex";
$current_page = "profilo";

// Variables for messages
$password_error = '';
$password_success = '';

// Handle password change
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    try {
        // Verify current password
        $stmt = $db->prepare("SELECT pwd FROM Utente WHERE username = :username");
        $stmt->execute(['username' => $_SESSION['user_id']]);
        $user = $stmt->fetch();

        if ($current_password !== $user['pwd']) {
            $password_error = "La password corrente non è corretta";
        } elseif ($new_password !== $confirm_password) {
            $password_error = "Le nuove password non coincidono";
        } else {
            // Update password
            $stmt = $db->prepare("UPDATE Utente SET pwd = :password WHERE username = :username");
            $stmt->execute([
                'password' => $new_password,
                'username' => $_SESSION['user_id']
            ]);
            $password_success = "Password modificata con successo!";
        }
    } catch(PDOException $e) {
        $password_error = "Errore durante l'aggiornamento della password: " . $e->getMessage();
    }
}

// Get all events (both paid and not paid) with visit details
$prenotazioni = [];
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
            p.stato,
            g.nome AS guida_nome,
            g.cognome AS guida_cognome
        FROM Evento_prenotato p
        JOIN Evento e ON p.id_evento = e.id
        JOIN Evento_Visita ev ON e.id = ev.id_evento
        JOIN Visita v ON ev.visita = v.titolo
        JOIN Guida g ON e.guida = g.id
        WHERE p.utente = :user_id
        ORDER BY p.stato DESC
    ");
    $stmt->execute(['user_id' => $_SESSION['user_id']]);
    $prenotazioni = $stmt->fetchAll();
} catch(PDOException $e) {
    $prenotazioni_error = "Errore nel recupero delle prenotazioni: " . $e->getMessage();
}

include 'header.php';
?>

    <div class="profile-container">
        <div class="profile-header">
            <h1>Benvenuto, <?php echo htmlspecialchars($_SESSION['user_name']); ?></h1>
        </div>

        <div class="profile-sections">
            <!-- Booked Events Section -->
            <section class="profile-section">
                <h2>I tuoi eventi prenotati</h2>

                <?php if (!empty($prenotazioni_error)): ?>
                    <div class="error"><?php echo $prenotazioni_error; ?></div>
                <?php endif; ?>

                <?php if (empty($prenotazioni)): ?>
                    <p>Non hai ancora prenotato nessun evento.</p>
                <?php else: ?>
                    <div class="event-list">
                        <br>
                        <?php foreach ($prenotazioni as $evento): ?>
                            <div class="event-card <?php echo $evento['stato'] === 'pagato' ? 'paid' : 'unpaid'; ?>">
                                <div class="event-image">
                                    <img src="<?php echo htmlspecialchars($evento['img']); ?>" alt="<?php echo htmlspecialchars($evento['visita_titolo']); ?>">
                                </div>
                                <div class="event-details">
                                    <h3><?php echo htmlspecialchars($evento['visita_titolo']); ?></h3>
                                    <p class="status <?php echo $evento['stato']; ?>">
                                        <strong>Stato:</strong>
                                        <?php
                                        echo $evento['stato'] === 'pagato'
                                            ? 'Pagato'
                                            : 'In attesa di pagamento';
                                        ?>
                                    </p>
                                    <p><strong>Luogo:</strong> <?php echo htmlspecialchars($evento['luogo']); ?></p>
                                    <p><strong>Durata:</strong> <?php echo $evento['durata']; ?> minuti</p>
                                    <p><strong>Lingua:</strong> <?php echo htmlspecialchars($evento['lingua']); ?></p>
                                    <p><strong>Guida:</strong> <?php echo htmlspecialchars($evento['guida_nome'] . ' ' . $evento['guida_cognome']); ?></p>
                                    <p><strong>Prezzo:</strong> €<?php echo number_format($evento['prezzo'], 2); ?></p>

                                    <?php if ($evento['stato'] === 'prenotato'): ?>
                                        <a href="pagamento.php?evento_id=<?php echo $evento['evento_id']; ?>" class="btn pay-btn">Completa il pagamento</a>
                                    <?php else: ?>
                                        <a href="stampa_biglietto.php?evento_id=<?php echo $evento['evento_id']; ?>" class="btn pdf-btn" target="_blank">Stampa Ricevuta PDF</a>

                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Password Change Section -->
            <section class="profile-section">
                <h2>Modifica Password</h2>

                <?php if ($password_error): ?>
                    <div class="error"><?php echo $password_error; ?></div>
                <?php endif; ?>

                <?php if ($password_success): ?>
                    <div class="success"><?php echo $password_success; ?></div>
                <?php endif; ?>

                <form method="POST" class="password-form">
                    <div class="form-group">
                        <label for="current_password">Password corrente</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>

                    <div class="form-group">
                        <label for="new_password">Nuova password</label>
                        <input type="password" id="new_password" name="new_password" required>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Conferma nuova password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>

                    <button type="submit" name="change_password" class="btn">Cambia Password</button>
                </form>
            </section>
        </div>
    </div>

<?php include 'footer.php'; ?>