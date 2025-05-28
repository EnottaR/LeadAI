<form action="add_lead.php" method="POST">
    <!-- Dati visibili del lead -->
    <label for="name">Nome:</label>
    <input type="text" name="name" required>

    <label for="surname">Cognome:</label>
    <input type="text" name="surname" required>

    <label for="email">Email:</label>
    <input type="email" name="email" required>

    <label for="phone">Telefono:</label>
    <input type="text" name="phone" required>

    <label for="message">Messaggio:</label>
    <textarea name="message" required></textarea>

    <!-- client_id -->
    <input type="hidden" name="clients_id" value="3">

    <button type="submit">INVIA</button>
</form>
