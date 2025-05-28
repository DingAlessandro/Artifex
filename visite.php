<?php
// Avvio sicuro della sessione
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configurazione database
$config = require('databaseConfig.php');
require_once('DBcon.php');
$db = DBcon::getDB($config);

$page_title = "Visite | Artifex";
$current_page = "visite";

// Gestione filtri
$lingua_selezionata = $_GET['lingua'] ?? '';

// Query base per le visite
$query_visite = "SELECT * FROM Visita";

// Aggiungi filtro lingua se selezionato
if (!empty($lingua_selezionata)) {
    $query_visite .= " WHERE titolo IN (
        SELECT visita FROM Evento_Visita 
        JOIN Evento ON Evento_Visita.id_evento = Evento.id 
        WHERE Evento.lingua = :lingua
    )";
}

$query_visite .= " ORDER BY titolo";

// Recupera visite filtrate
$visite = [];
try {
    $stmt = $db->prepare($query_visite);

    if (!empty($lingua_selezionata)) {
        $stmt->execute(['lingua' => $lingua_selezionata]);
    } else {
        $stmt->execute();
    }

    $visite = $stmt->fetchAll();
} catch(PDOException $e) {
    $visite_error = "Errore nel recupero delle visite: " . $e->getMessage();
}

// Recupera tutte le lingue disponibili per il filtro
try {
    $stmt = $db->query("SELECT * FROM Lingua ORDER BY lingua");
    $lingue = $stmt->fetchAll();
} catch(PDOException $e) {
    $lingue = [];
}

include 'header.php';
?>

    <div class="visite-container">
        <h1>Le nostre visite guidate</h1>

        <?php if (!empty($visite_error)): ?>
            <div class="error"><?php echo $visite_error; ?></div>
        <?php endif; ?>

        <!-- Filtri -->
        <div class="filters">
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label for="lingua">Filtra per lingua:</label>
                    <select id="lingua" name="lingua" onchange="this.form.submit()">
                        <option value="">Tutte le lingue</option>
                        <?php foreach ($lingue as $lingua): ?>
                            <option value="<?php echo htmlspecialchars($lingua['lingua']); ?>"
                                <?php echo ($lingua_selezionata === $lingua['lingua']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($lingua['lingua']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>

        <!-- Lista visite -->
        <div class="visite-list">
            <?php if (empty($visite)): ?>
                <p class="no-results">Nessuna visita disponibile con i filtri selezionati.</p>
            <?php else: ?>
                <?php foreach ($visite as $visita): ?>
                    <div class="visita-card">
                        <div class="visita-image">
                            <img src="<?php echo htmlspecialchars($visita['img']); ?>" alt="<?php echo htmlspecialchars($visita['titolo']); ?>">
                        </div>
                        <div class="visita-details">
                            <h2><?php echo htmlspecialchars($visita['titolo']); ?></h2>
                            <p><strong>Luogo:</strong> <?php echo htmlspecialchars($visita['luogo']); ?></p>
                            <p><strong>Durata:</strong> <?php echo htmlspecialchars($visita['durata']); ?> minuti</p>

                            <!-- Eventi disponibili per questa visita -->
                            <?php
                            try {
                                $query_eventi = "
                                SELECT e.*, g.nome AS guida_nome, g.cognome AS guida_cognome 
                                FROM Evento e
                                JOIN Evento_Visita ev ON e.id = ev.id_evento
                                JOIN Guida g ON e.guida = g.id
                                WHERE ev.visita = :titolo
                            ";

                                // Aggiungi filtro lingua se attivo
                                if (!empty($lingua_selezionata)) {
                                    $query_eventi .= " AND e.lingua = :lingua";
                                }

                                $query_eventi .= " ORDER BY e.lingua";

                                $stmt = $db->prepare($query_eventi);
                                $params = ['titolo' => $visita['titolo']];

                                if (!empty($lingua_selezionata)) {
                                    $params['lingua'] = $lingua_selezionata;
                                }

                                $stmt->execute($params);
                                $eventi = $stmt->fetchAll();
                            } catch(PDOException $e) {
                                $eventi = [];
                            }
                            ?>

                            <div class="eventi-disponibili">
                                <h3>Prossime disponibilità:</h3>
                                <?php if (!empty($eventi)): ?>
                                    <ul>
                                        <?php foreach ($eventi as $evento): ?>
                                            <li>
                                                <strong><?php echo htmlspecialchars($evento['lingua']); ?></strong> -
                                                Con <?php echo htmlspecialchars($evento['guida_nome'] . ' ' . $evento['guida_cognome']); ?> -
                                                €<?php echo number_format($evento['prezzo'], 2); ?>

                                                <?php if (isset($_SESSION['user_id'])): ?>
                                                    <a href="prenota.php?evento_id=<?php echo $evento['id']; ?>" class="btn btn-small">Prenota</a>
                                                <?php else: ?>
                                                    <a href="login.php" class="btn btn-small">Accedi per prenotare</a>
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <p>Nessun evento disponibile con i filtri selezionati.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

<?php include 'footer.php'; ?>