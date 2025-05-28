<?php
$config = require('databaseConfig.php');
require_once('DBcon.php');
require_once('functions.php');
$db = DBcon::getDB($config);
?>


<?php
include 'header.php';
?>

<section class="hero">
    <div class="container">
        <div class="hero-content">
            <h1>Scopri il patrimonio culturale italiano</h1>
            <p>Dalle meraviglie di Roma all'arte di Firenze, dai siti archeologici di Pompei alle bellezze di Venezia. Visite guidate con esperti qualificati in più lingue.</p>
            <a href="#" class="btn">Esplora le visite</a>
        </div>
    </div>
</section>

<section class="features">
    <div class="container">
        <div class="section-title">
            <h2>Perché scegliere Artifex?</h2>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">🏛️</div>
                <h3>Guide Specializzate</h3>
                <p>Le nostre guide sono esperti con formazione accademica in storia dell'arte, archeologia e architettura.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🌍</div>
                <h3>Servizi Multilingua</h3>
                <p>Offriamo visite guidate in italiano, inglese, francese, spagnolo, tedesco e altre lingue.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🎫</div>
                <h3>Prenotazione Facile</h3>
                <p>Sistema di prenotazione online semplice con possibilità di aggiungere più visite al carrello.</p>
            </div>
        </div>
    </div>
</section>

<section class="popular-tours">
    <div class="container">
        <div class="section-title">
            <h2>Le nostre visite più popolari</h2>
        </div>
        <div class="tours-grid">
            <?php
            $sql = "SELECT v.titolo, v.durata, v.luogo, v.img, 
                           COUNT(ep.id_evento) as prenotazioni, 
                           MIN(e.prezzo) as prezzo_minimo
                    FROM Visita v
                    JOIN Evento_Visita ev ON v.titolo = ev.visita
                    JOIN Evento e ON ev.id_evento = e.id
                    LEFT JOIN Evento_prenotato ep ON e.id = ep.id_evento
                    GROUP BY v.titolo
                    ORDER BY prenotazioni DESC
                    LIMIT 3";
            $result = $db->query($sql);

            if ($result && $result->rowCount() > 0) {
                while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                    $titolo = htmlspecialchars($row['titolo']);
                    $durata = $row['durata'];
                    $luogo = htmlspecialchars($row['luogo']);
                    $img = htmlspecialchars($row['img']);
                    $prezzo_minimo = $row['prezzo_minimo'];

                    // Formatta la durata
                    $durata_ore = $durata / 60;
                    $formatted = number_format($durata_ore, 1);
                    $formatted = rtrim(rtrim($formatted, '0'), '.');
                    $durata_formattata = $formatted . ' ore';

                    // Formatta il prezzo
                    $prezzo_formattato = number_format($prezzo_minimo, 2, ',', '.');
                    $prezzo_formattato = preg_replace('/,00$/', '', $prezzo_formattato);
                    ?>
                    <div class="tour-card">
                        <a href="visite.php" class="tour-link" style="text-decoration:none; color:inherit;">
                            <div class="tour-img">
                                <img src="<?= $img ?>" alt="<?= $titolo ?>">
                            </div>
                            <div class="tour-info">
                                <h3><?= $titolo ?></h3>
                                <div class="tour-meta">
                                    <span>Durata: <?= $durata_formattata ?></span>
                                    <span><?= $luogo ?></span>
                                </div>
                                <div class="tour-price">
                                    <span>da €<?= $prezzo_formattato ?></span>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php
                }
            } else {
                echo '<p>Al momento non ci sono visite popolari disponibili.</p>';
            }
            ?>
        </div>
    </div>
</section>

<?php
include 'footer.php';
?>
</body>
</html>