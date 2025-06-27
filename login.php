<?php
session_start();
require 'includes/db.php';
require 'includes/auth.php';
require 'includes/csrf.php';

$messaggio = "";
$errore_email = "";
$errore_password = "";
$errore_registrazione = "";

if (isset($_GET['session_expired'])) {
    $messaggio = "⚠️ La tua sessione è scaduta. Effettua di nuovo il login.";
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
        
        if (empty($nome) || empty($cognome)) {
            $errore_registrazione = "Nome e cognome sono obbligatori.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errore_registrazione = "Inserisci un indirizzo email valido.";
        } elseif (strlen($password) < 6) {
            $errore_registrazione = "La password deve contenere almeno 6 caratteri.";
        } else {
            $result = register_user($conn, $nome, $cognome, $email, $password);
            if ($result['type'] === 'success') {
                $messaggio = $result['message'];
            } else {
                $errore_registrazione = $result['message'];
            }
        }
    } elseif (isset($_POST['login'])) {
        $login = trim($_POST['email']);
        $password = $_POST['password'];
        
        if (empty($login)) {
            $errore_email = "L'indirizzo email è obbligatorio.";
        } elseif (!filter_var($login, FILTER_VALIDATE_EMAIL)) {
            $errore_email = "Inserisci un indirizzo email valido.";
        }
        
        if (empty($password)) {
            $errore_password = "La password è obbligatoria.";
        }
        
        if (empty($errore_email) && empty($errore_password)) {
            $stmt = $conn->prepare("SELECT id, password FROM clients WHERE email = ? OR username = ?");
            $stmt->bind_param("ss", $login, $login);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $errore_email = "Questo indirizzo email non è registrato.";
            } else {
                $user = $result->fetch_assoc();
                if (!password_verify($password, $user['password'])) {
                    $errore_password = "Password non corretta.";
                } else {
                    $login_result = login_user($conn, $login, $password);
                    if ($login_result === true) {
                        header("Location: dashboard.php");
                        exit;
                    } else {
                        $messaggio = "Errore durante il login. Riprova.";
                    }
                }
            }
            $stmt->close();
        }
    }
}

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
                
                <?php if (!empty($messaggio)): ?>
                    <div class="msg-successo">
                        <?= htmlspecialchars($messaggio) ?>
                    </div>
                <?php endif; ?>

                <div id="login-fields" class="active">
                    <form method="POST" action="login.php">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="login" value="1">
                        
                        <div class="input-container show">
                            <input type="email" name="email" placeholder="Email*" required 
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                   class="<?= !empty($errore_email) ? 'input-error' : '' ?>">
                            <?php if (!empty($errore_email)): ?>
                                <div class="error-message">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <?= htmlspecialchars($errore_email) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="password-container show">
                            <input type="password" name="password" id="password" placeholder="Password*" required
                                   class="<?= !empty($errore_password) ? 'input-error' : '' ?>">
                            <i id="toggle-password" class="pass-icona fas fa-eye"></i>
                            <?php if (!empty($errore_password)): ?>
                                <div class="error-message">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <?= htmlspecialchars($errore_password) ?>
                                </div>
                            <?php endif; ?>
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

                        <?php if (!empty($errore_registrazione)): ?>
                            <div class="error-message registration-error">
                                <i class="fas fa-exclamation-circle"></i>
                                <?= htmlspecialchars($errore_registrazione) ?>
                            </div>
                        <?php endif; ?>

                        <div class="input-container">
                            <input type="text" name="nome" placeholder="Nome*" required
                                   value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>">
                        </div>
                        <div class="input-container">
                            <input type="text" name="cognome" placeholder="Cognome*" required
                                   value="<?= htmlspecialchars($_POST['cognome'] ?? '') ?>">
                        </div>
                        <div class="input-container">
                            <input type="email" name="registra_mail" placeholder="Email*" required
                                   value="<?= htmlspecialchars($_POST['registra_mail'] ?? '') ?>">
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
</body>
</html>