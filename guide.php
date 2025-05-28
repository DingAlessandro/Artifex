<?php
// Avvio sicuro della sessione
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configurazione database
$config = require('databaseConfig.php');
require_once('DBcon.php');
$db = DBcon::getDB($config);

$page_title = "Le nostre Guide | Artifex";
$current_page = "guide";

// Recupera tutte le guide disponibili SENZA DUPLICATI
$guide = [];
try {
    // Prima query: recupera solo le guide (senza JOIN per evitare duplicati)
    $stmt = $db->query("SELECT * FROM Guida ORDER BY cognome, nome");
    $guide = $stmt->fetchAll();

    // Per ogni guida, recupera le lingue parlate
    foreach ($guide as &$guida) {
        $stmt_lingue = $db->prepare("
            SELECT lingua, livello 
            FROM Lingua_Guida 
            WHERE id_guida = :id_guida
            ORDER BY lingua
        ");
        $stmt_lingue->execute(['id_guida' => $guida['id']]);
        $guida['lingue'] = $stmt_lingue->fetchAll();
    }
    unset($guida); // Rompe il riferimento

} catch(PDOException $e) {
    $guide_error = "Errore nel recupero delle guide: " . $e->getMessage();
}

include 'header.php';
?>

    <div class="guide-container">
        <h1>Le nostre Guide Turistiche</h1>

        <?php if (!empty($guide_error)): ?>
            <div class="error"><?php echo $guide_error; ?></div>
        <?php endif; ?>

        <div class="guide-list">
            <?php if (empty($guide)): ?>
                <p class="no-guide">Nessuna guida disponibile al momento.</p>
            <?php else: ?>
                <?php foreach ($guide as $guida): ?>
                    <div class="guide-card">
                        <div class="guide-image">
                            <img src="https://blogger.googleusercontent.com/img/a/AVvXsEgM85LEvtgJrUmGx95tmMptVQWhITwGOun2FEdThRoHM1iA2IV7J9KA94UtTJco4GVIvitht8kY-nc9U6SBQ6oLlsGCLSlr2S0dv9m04sUFE_suAa77Z8V-HxOFVxSsPMEqRgGGlC0ilOpb-a_tAjkvTr_ux8GfLjdCFWkj8HVQ-kGVukNz9WfrQ9_s1g=s623"
                                 alt="<?php echo htmlspecialchars($guida['nome'] . ' ' . $guida['cognome']); ?>">
                        </div>
                        <div class="guide-details">
                            <h2><?php echo htmlspecialchars($guida['nome'] . ' ' . $guida['cognome']); ?></h2>

                            <div class="guide-info">
                                <p><strong>Titolo di studio:</strong> <?php echo htmlspecialchars($guida['titolo_studio']); ?></p>
                                <p><strong>Luogo di nascita:</strong> <?php echo htmlspecialchars($guida['luogo_nascita']); ?></p>
                                <p><strong>Data di nascita:</strong> <?php echo date('d/m/Y', strtotime($guida['data_nascita'])); ?></p>
                                <p><strong>Età:</strong> <?php echo date_diff(date_create($guida['data_nascita']), date_create('today'))->y; ?> anni</p>
                            </div>

                            <div class="guide-languages">
                                <h3>Lingue parlate:</h3>
                                <?php if (!empty($guida['lingue'])): ?>
                                    <ul>
                                        <?php foreach ($guida['lingue'] as $lingua): ?>
                                            <li>
                                                <?php echo htmlspecialchars($lingua['lingua']); ?>
                                                <span class="language-level">(<?php echo htmlspecialchars($lingua['livello']); ?>)</span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <p>Nessuna lingua registrata</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

<?php include 'footer.php'; ?>