<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aggiungi Cliente</title>
</head>
<body>
    <h2>Aggiungi Cliente</h2>
    <form action="add_client.php" method="POST">
        <label for="name">Nome Cliente:</label>
        <input type="text" id="name" name="name" required><br><br>

        <label for="surname">Cognome Cliente:</label>
        <input type="text" id="surname" name="surname" required><br><br>

        <label for="email">Email Cliente:</label>
        <input type="email" id="email" name="email" required><br><br>

        <label for="company">Azienda (opzionale):</label>
        <input type="text" id="company" name="company"><br><br>

        <label for="username">Username Cliente:</label>
        <input type="text" id="username" name="username" required><br><br>

        <label for="password">Password Cliente:</label>
        <input type="password" id="password" name="password" required><br><br>

        <label for="website_name">Nome Sito Web:</label>
        <input type="text" id="website_name" name="website_name" required><br><br>

        <label for="website_url">URL Sito Web:</label>
        <input type="url" id="website_url" name="website_url" required><br><br>

        <button type="submit">Aggiungi</button>
    </form>
</body>
</html>