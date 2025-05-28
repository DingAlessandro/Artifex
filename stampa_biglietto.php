<?php
require_once 'vendor/tecnickcom/tcpdf/tcpdf.php';
require_once 'DBcon.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_GET['evento_id'], $_SESSION['user_id'])) die("Errore: dati mancanti.");

$config = require('databaseConfig.php');
$db = DBcon::getDB($config);

$evento_id = $_GET['evento_id'];
$username = $_SESSION['user_id'];

try {
    $stmt = $db->prepare("
        SELECT 
            u.nome AS user_nome,
            v.titolo AS visita_titolo,
            v.luogo,
            v.durata,
            e.prezzo,
            g.nome AS guida_nome,
            g.cognome AS guida_cognome,
            v.img
        FROM Evento_prenotato p
        JOIN Utente u ON p.utente = u.username
        JOIN Evento e ON p.id_evento = e.id
        JOIN Evento_Visita ev ON e.id = ev.id_evento
        JOIN Visita v ON ev.visita = v.titolo
        JOIN Guida g ON e.guida = g.id
        WHERE p.utente = :username AND p.id_evento = :evento_id AND p.stato = 'pagato'
    ");
    $stmt->execute(['username' => $username, 'evento_id' => $evento_id]);
    $dati = $stmt->fetch();
    if (!$dati) die("Nessun biglietto trovato.");

    // Inizializza PDF (A5)
    $pdf = new TCPDF('P', 'mm', 'A5', true, 'UTF-8', false);
    $pdf->SetMargins(15, 15, 15);
    $pdf->AddPage();
    $pdf->SetAutoPageBreak(true, 20);

    // Titolo biglietto
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->SetTextColor(33, 64, 154);
    $pdf->Cell(0, 10, 'Biglietto Evento', 0, 1, 'C');
    $pdf->Ln(5);

    // Immagine visita (centro)
    if (!empty($dati['img'])) {
        // Ridimensiona a max 80mm larghezza e 45mm altezza, mantenendo proporzioni
        list($width, $height) = getimagesize($dati['img']);
        $maxWidth = 80;
        $maxHeight = 45;
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        $imgWidth = $width * $ratio;
        $imgHeight = $height * $ratio;

        $x = ($pdf->getPageWidth() - $imgWidth) / 2;
        $y = $pdf->GetY();
        $pdf->Image($dati['img'], $x, $y, $imgWidth, $imgHeight);
        $pdf->Ln($imgHeight + 5);
    }

    // Dettagli visita
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetTextColor(33, 64, 154);
    $pdf->Cell(0, 8, 'Dettagli Visita', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 11);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->MultiCell(0, 0,
        "Titolo: {$dati['visita_titolo']}\n" .
        "Luogo: {$dati['luogo']}\n" .
        "Durata: {$dati['durata']} minuti\n" .
        "Guida: {$dati['guida_nome']} {$dati['guida_cognome']}\n" .
        "Prezzo:€".$dati['prezzo']
    );
    $pdf->Ln(3);

    // Dati utente
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetTextColor(33, 64, 154);
    $pdf->Cell(0, 8, 'Dati Partecipante', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 11);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 6, "Nome: {$dati['user_nome']}", 0, 1, 'L');
    $pdf->Ln(5);

    // Codice biglietto e QR code (centrato)
    $codice = strtoupper(substr(md5(uniqid()), 0, 8));
    $qrData = "Evento: {$dati['visita_titolo']}\nUtente: {$dati['user_nome']}\nCodice: $codice";

    $qrSize = 40;
    $qrX = ($pdf->getPageWidth() - $qrSize) / 2;
    $qrY = $pdf->GetY();
    $pdf->write2DBarcode($qrData, 'QRCODE,L', $qrX, $qrY, $qrSize, $qrSize);
    $pdf->SetY($qrY + $qrSize + 2);
    $pdf->SetFont('helvetica', 'I', 10);
    $pdf->Cell(0, 5, "Codice biglietto: $codice", 0, 1, 'C');



    $pdf->Output("Biglietto_{$dati['visita_titolo']}.pdf", 'I');

} catch (PDOException $e) {
    die("Errore DB: " . $e->getMessage());
}
