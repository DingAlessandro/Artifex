<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica che l'utente sia un amministratore
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'amministratore') {
    header("Location: login.php");
    exit();
}

$page_title = "Dashboard Amministratore | Artifex";
$current_page = "admin_dashboard";

// Carica la configurazione del database e stabilisci la connessione
$config = require('databaseConfig.php');
require_once('DBcon.php');
require_once('functions.php');
$db = DBcon::getDB($config);

// Gestione dei messaggi di operazione
$success_message = '';
$error_message = '';

// Statistiche generali per la dashboard
try {
    // Numero totale di eventi
    $stmt = $db->query("SELECT COUNT(*) AS total FROM Evento");
    $total_events = $stmt->fetch()['total'];

    // Numero totale di guide
    $stmt = $db->query("SELECT COUNT(*) AS total FROM Guida");
    $total_guides = $stmt->fetch()['total'];

    // Numero totale di utenti turisti
    $stmt = $db->query("SELECT COUNT(*) AS total FROM Utente WHERE tipo = 'turista'");
    $total_users = $stmt->fetch()['total'];

    // Numero totale di prenotazioni
    $stmt = $db->query("SELECT COUNT(*) AS total FROM Evento_prenotato");
    $total_bookings = $stmt->fetch()['total'];

    // Numero totale di visite
    $stmt = $db->query("SELECT COUNT(*) AS total FROM Visita");
    $total_visits = $stmt->fetch()['total'];

} catch(PDOException $e) {
    $error_message = "Errore nel recupero delle statistiche: " . $e->getMessage();
}

?>
    <link rel="stylesheet" href="style/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <div class="admin-dashboard">
        <div class="sidebar">
            <div class="admin-info">
                <div class="admin-avatar">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="admin-details">
                    <h3><?= htmlspecialchars($_SESSION['user_name']) ?></h3>
                    <p>Amministratore</p>
                </div>
            </div>
            <ul class="admin-menu">
                <li class="active"><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="admin_eventi.php"><i class="fas fa-calendar-alt"></i> Gestione Eventi</a></li>
                <li><a href="admin_guide.php"><i class="fas fa-user-tie"></i> Gestione Guide</a></li>
                <li><a href="admin_visite.php"><i class="fas fa-monument"></i> Gestione Visite</a></li>
                <li><a href="admin_utenti.php"><i class="fas fa-users"></i> Gestione Utenti</a></li>
                <li><a href="admin_lingue.php"><i class="fas fa-language"></i> Gestione Lingue</a></li>
                <li><a href="admin_prenotazioni.php"><i class="fas fa-ticket-alt"></i> Gestione Prenotazioni</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="page-header">
                <h1>Dashboard Amministratore</h1>
                <p>Benvenuto, <?= htmlspecialchars($_SESSION['user_name']) ?>! Qui puoi gestire tutti i contenuti della piattaforma Artifex.</p>
            </div>

            <?php if ($success_message): ?>
                <div class="alert success"><?= $success_message ?></div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert error"><?= $error_message ?></div>
            <?php endif; ?>

            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $total_events ?></h3>
                        <p>Eventi totali</p>
                    </div>
                    <a href="admin_eventi.php" class="stat-link">Gestisci</a>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $total_guides ?></h3>
                        <p>Guide totali</p>
                    </div>
                    <a href="admin_guide.php" class="stat-link">Gestisci</a>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $total_users ?></h3>
                        <p>Utenti registrati</p>
                    </div>
                    <a href="admin_utenti.php" class="stat-link">Gestisci</a>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $total_bookings ?></h3>
                        <p>Prenotazioni totali</p>
                    </div>
                    <a href="admin_prenotazioni.php" class="stat-link">Gestisci</a>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-monument"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $total_visits ?></h3>
                        <p>Visite disponibili</p>
                    </div>
                    <a href="admin_visite.php" class="stat-link">Gestisci</a>
                </div>
            </div>

            <div class="recent-data">
                <div class="recent-section">
                    <h2>Eventi recenti</h2>
                    <div class="recent-table-container">
                        <table class="recent-table">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Lingua</th>
                                <th>Prezzo</th>
                                <th>Guida</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            try {
                                $stmt = $db->query("SELECT e.id, e.lingua, e.prezzo, CONCAT(g.nome, ' ', g.cognome) as guida_nome 
                                                   FROM Evento e 
                                                   JOIN Guida g ON e.guida = g.id 
                                                   ORDER BY e.id DESC LIMIT 5");
                                $recent_events = $stmt->fetchAll();

                                if (count($recent_events) > 0) {
                                    foreach ($recent_events as $event) {
                                        echo "<tr>";
                                        echo "<td>" . $event['id'] . "</td>";
                                        echo "<td>" . htmlspecialchars($event['lingua']) . "</td>";
                                        echo "<td>€" . number_format($event['prezzo'], 2) . "</td>";
                                        echo "<td>" . htmlspecialchars($event['guida_nome']) . "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='4'>Nessun evento trovato</td></tr>";
                                }
                            } catch(PDOException $e) {
                                echo "<tr><td colspan='4'>Errore nel recupero degli eventi recenti</td></tr>";
                            }
                            ?>
                            </tbody>
                        </table>
                    </div>
                    <a href="admin_eventi.php" class="view-all">Visualizza tutti &rarr;</a>
                </div>

                <div class="recent-section">
                    <h2>Prenotazioni recenti</h2>
                    <div class="recent-table-container">
                        <table class="recent-table">
                            <thead>
                            <tr>
                                <th>Evento</th>
                                <th>Utente</th>
                                <th>Stato</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            try {
                                $stmt = $db->query("SELECT ep.id_evento, ep.utente, ep.stato, 
                                                   CONCAT(g.nome, ' ', g.cognome) as guida_nome, 
                                                   ev.lingua
                                                   FROM Evento_prenotato ep
                                                   JOIN Evento ev ON ep.id_evento = ev.id
                                                   JOIN Guida g ON ev.guida = g.id
                                                   ORDER BY ep.id_evento DESC LIMIT 5");
                                $recent_bookings = $stmt->fetchAll();

                                if (count($recent_bookings) > 0) {
                                    foreach ($recent_bookings as $booking) {
                                        $status_class = ($booking['stato'] == 'pagato') ? 'paid' : 'booked';

                                        echo "<tr>";
                                        echo "<td>ID: " . $booking['id_evento'] . " - " . htmlspecialchars($booking['lingua']) . "</td>";
                                        echo "<td>" . htmlspecialchars($booking['utente']) . "</td>";
                                        echo "<td><span class='status-badge " . $status_class . "'>" . ucfirst($booking['stato']) . "</span></td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='3'>Nessuna prenotazione trovata</td></tr>";
                                }
                            } catch(PDOException $e) {
                                echo "<tr><td colspan='3'>Errore nel recupero delle prenotazioni recenti</td></tr>";
                            }
                            ?>
                            </tbody>
                        </table>
                    </div>
                    <a href="admin_prenotazioni.php" class="view-all">Visualizza tutte &rarr;</a>
                </div>
            </div>

            <div class="quick-actions">
                <h2>Azioni rapide</h2>
                <div class="actions-container">
                    <a href="admin_eventi.php?action=create" class="action-card">
                        <i class="fas fa-plus-circle"></i>
                        <span>Nuovo Evento</span>
                    </a>
                    <a href="admin_guide.php?action=create" class="action-card">
                        <i class="fas fa-user-plus"></i>
                        <span>Nuova Guida</span>
                    </a>
                    <a href="admin_visite.php?action=create" class="action-card">
                        <i class="fas fa-monument"></i>
                        <span>Nuova Visita</span>
                    </a>
                    <a href="admin_lingue.php?action=create" class="action-card">
                        <i class="fas fa-language"></i>
                        <span>Nuova Lingua</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
