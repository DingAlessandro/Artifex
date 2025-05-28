<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$page_title = "Login | Artifex";
$current_page = "login";

// Carica la configurazione del database e stabilisci la connessione
$config = require('databaseConfig.php');
require_once('DBcon.php');
require_once('functions.php');
$db = DBcon::getDB($config);

$login_error = '';
$register_error = '';
$register_success = '';

// Gestione del login
if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    try {
        $stmt = $db->prepare("SELECT * FROM Utente WHERE email = :email");
        $stmt->execute(['email' => $email]);

        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch();

            if ($password === $user['pwd']) {
                $_SESSION['user_id'] = $user['username'];
                $_SESSION['user_name'] = $user['nome'];
                $_SESSION['user_type'] = $user['tipo'];

                if ($user['tipo'] === 'amministratore') {
                    header("Location: admin_dashboard.php");
                } else {
                    header("Location: index.php");
                }
                exit();
            } else {
                $login_error = "Password non corretta";
            }
        } else {
            $login_error = "Email non trovata";
        }
    } catch(PDOException $e) {
        $login_error = "Errore durante il login: " . $e->getMessage();
    }
}

// Gestione della registrazione
if (isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $nationality = $_POST['nationality'] ?? null;
    $phone = $_POST['phone'] ?? null;
    $language = $_POST['language'] ?? null;

    try {
        $stmt = $db->prepare("SELECT * FROM Utente WHERE username = :username");
        $stmt->execute(['username' => $username]);

        if ($stmt->rowCount() > 0) {
            $register_error = "Username già in uso";
        } else {
            $stmt = $db->prepare("SELECT * FROM Utente WHERE email = :email");
            $stmt->execute(['email' => $email]);

            if ($stmt->rowCount() > 0) {
                $register_error = "Email già in uso";
            } else {
                $stmt = $db->prepare("INSERT INTO Utente (username, pwd, nome, email, nazionalita, telefono, lingua, tipo) 
                                    VALUES (:username, :password, :name, :email, :nationality, :phone, :language, 'turista')");

                $stmt->execute([
                    'username' => $username,
                    'password' => $password,
                    'name' => $name,
                    'email' => $email,
                    'nationality' => $nationality,
                    'phone' => $phone,
                    'language' => $language
                ]);

                $register_success = "Registrazione completata! Ora puoi accedere.";
            }
        }
    } catch(PDOException $e) {
        $register_error = "Errore durante la registrazione: " . $e->getMessage();
    }
}

// Recupera le lingue disponibili
try {
    $stmt = $db->query("SELECT * FROM Lingua ORDER BY lingua");
    $languages = $stmt->fetchAll();
} catch(PDOException $e) {
    $languages = [];
}
?>

<?php include 'header.php'; ?>

<div class="login-container">
    <div class="login-box">
        <div class="login-tabs">
            <div class="login-tab active" onclick="switchTab('login')">Accedi</div>
            <div class="login-tab" onclick="switchTab('register')">Registrati</div>
        </div>

        <div class="login-content">
            <!-- Form di Login -->
            <div id="login-tab" class="tab-content active">
                <h2>Accedi al tuo account</h2>
                <?php if ($login_error): ?>
                    <div class="error"><?= $login_error ?></div>
                <?php endif; ?>
                <?php if ($register_success): ?>
                    <div class="success"><?= $register_success ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="form-group">
                        <label for="login-email">Email</label>
                        <input type="email" id="login-email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="login-password">Password</label>
                        <input type="password" id="login-password" name="password" required>
                    </div>
                    <button type="submit" name="login" class="btn">Accedi</button>
                </form>
            </div>

            <!-- Form di Registrazione -->
            <div id="register-tab" class="tab-content">
                <h2>Crea un nuovo account</h2>
                <?php if ($register_error): ?>
                    <div class="error"><?= $register_error ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="register-username">Username</label>
                            <input type="text" id="register-username" name="username" required>
                        </div>
                        <div class="form-group">
                            <label for="register-name">Nome completo</label>
                            <input type="text" id="register-name" name="name" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="register-email">Email</label>
                        <input type="email" id="register-email" name="email" required>
                    </div>

                    <div class="form-group">
                        <label for="register-password">Password</label>
                        <input type="password" id="register-password" name="password" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="register-nationality">Nazionalità</label>
                            <input type="text" id="register-nationality" name="nationality">
                        </div>
                        <div class="form-group">
                            <label for="register-phone">Telefono</label>
                            <input type="tel" id="register-phone" name="phone">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="register-language">Lingua preferita</label>
                        <select id="register-language" name="language">
                            <?php foreach ($languages as $lang): ?>
                                <option value="<?= $lang['lingua'] ?>"><?= $lang['lingua'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" name="register" class="btn">Registrati</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
<script>
    function switchTab(tabName) {
        document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
        document.getElementById(`${tabName}-tab`).classList.add('active');

        document.querySelectorAll('.login-tab').forEach(tab => tab.classList.remove('active'));
        event.currentTarget.classList.add('active');
    }
</script>
</body>
</html>