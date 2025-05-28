<?php
session_start();
require 'includes/db.php';
require 'includes/auth.php';
require 'includes/csrf.php';

$messaggio = "";
$session_conflict = false;

if (isset($_GET['session_expired'])) {
    $messaggio = "⚠️ La tua sessione è scaduta o è stata sostituita da un altro accesso. Effettua di nuovo il login.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($csrf_token)) {
        die("Errore di sicurezza: token CSRF non valido.");
    }

    if (isset($_POST['register'])) {
        $email = filter_var($_POST['registra_mail'], FILTER_SANITIZE_EMAIL);
        $password = $_POST['register_password'];
        $nome = $_POST['nome'] ?? '';
        $cognome = $_POST['cognome'] ?? '';
        $messaggio = register_user($conn, $nome, $cognome, $email, $password);
    } elseif (isset($_POST['login'])) {
        $login = trim($_POST['email']);
        $password = $_POST['password'];
        
        $login_result = login_user($conn, $login, $password);
        
        if ($login_result === true) {
            header("Location: dashboard.php");
            exit;
        } elseif (is_array($login_result) && $login_result['status'] === 'session_exists') {
            $messaggio = $login_result['message'];
            $session_conflict = true;
        } else {
            $messaggio = "Email o password errati. Controlla e riprova.";
        }
    } elseif (isset($_POST['force_login'])) {
        $login = trim($_POST['email']);
        $password = $_POST['password'];
        
        $stmt = $conn->prepare("SELECT id FROM clients WHERE email = ? OR username = ?");
        $stmt->bind_param("ss", $login, $login);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            forceLogout($conn, $user['id']);
            
            $login_result = login_user($conn, $login, $password);
            
            if ($login_result === true) {
                header("Location: dashboard.php");
                exit;
            } else {
                $messaggio = "Errore durante il login forzato. Riprova.";
            }
        }
        $stmt->close();
    }
}

// Token CSRF
$csrf_token = generate_csrf_token();
$conn->close();
?>

<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LeadAI - Accedi</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Jost:wght@500&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="assets/js/script.js"></script>
    
</head>

<body>
    <div class="login-container">
        <div class="form-container">
            <div class="login">
                <h2 id="form-title">Login</h2>
                
                <?php if ($session_conflict): ?>
                    <div class="msg-errore">
                        <h3><i class="fas fa-exclamation-triangle"></i> Attenzione</h3>
                        <p>L'account selezionato risulta già connesso da un altro dispositivo o browser.
						Se sei il proprietario dell'account, disconnettiti dalla sessione attiva e riprova.</p>

                        <form method="POST" action="login.php" id="force-login-form" style="display: none;">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="force_login" value="1">
                            <input type="hidden" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                            <input type="password" name="password" placeholder="Conferma password" required style="width: 100%; margin: 10px 0; padding: 8px;">
                            <button type="submit" class="force-login-btn">
                                <i class="fas fa-sign-out-alt"></i> Disconnetti altra sessione e accedi
                            </button>
                        </form>
                        <button onclick="cancelLogin()" class="cancel-btn">
                            Torna indietro
                        </button>
                    </div>
                <?php endif; ?>

                <div id="login-fields" class="<?= $session_conflict ? 'hidden' : 'active' ?>">
                    <form method="POST" action="login.php">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="login" value="1">
                        <div class="input-container show">
                            <input type="email" name="email" placeholder="Email*" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        </div>
                        <div class="password-container show">
                            <input type="password" name="password" id="password" placeholder="Password*" required>
                            <i id="toggle-password" class="pass-icona fas fa-eye"></i>
                        </div>
                        <button type="submit">Accedi</button>
                    </form>

                    <p class="divisorio">- oppure -</p>

                    <button id="toggle-button" onclick="toggleForms(true)">Registrati</button>
                </div>

                <div id="register-fields" class="hidden">
                    <form method="POST" action="login.php">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="register" value="1">

                        <div class="input-container">
                            <input type="text" name="nome" placeholder="Nome*" required>
                        </div>
                        <div class="input-container">
                            <input type="text" name="cognome" placeholder="Cognome*" required>
                        </div>
                        <div class="input-container">
                            <input type="email" name="registra_mail" placeholder="Email*" required>
                        </div>

                        <div class="password-container">
                            <input type="password" name="register_password" id="register-password" placeholder="Password*" required>
                            <i id="toggle-register-password" class="pass-icona fas fa-eye"></i>
                        </div>
                        <div class="password-strength" style="display: none;">
                            <div class="strength-bar">
                                <div id="progress-bar"></div>
                            </div>
                            <p id="strength-text" class="strength-text">Password debole</p>
                        </div>
                        <button type="submit" style="margin-bottom: 30px;">Crea Account</button>
                    </form>
                    <span class="accedi-link" onclick="toggleForms(false)">← Ho già un account</span>
                </div>
            </div>
        </div>
        <div class="image-container">
            <img src="assets/img/login-friend.svg" alt="Login Illustration">
            <div class="login-payoff">
                <strong>Gestisci i tuoi lead in modo intelligente!</strong>
                Analizza e converti i tuoi lead in maniera più efficace, senza complicazioni!
            </div>
        </div>
    </div>

    <script>
        function showForceLoginForm() {
            document.getElementById('force-login-form').style.display = 'block';
            document.getElementById('show-force-btn').style.display = 'none';
        }
        
        function cancelLogin() {
            window.location.href = 'login.php';
        }
    </script>
</body>
</html>