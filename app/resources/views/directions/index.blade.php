<!DOCTYPE html>
<html>
<head>
    <title>Directions</title>
</head>
<body>
    <h1>Directions</h1>

    <form method="POST" action="/directions">
        @csrf
        <label for="origin">出発地:</label>
        <input type="text" name="origin" id="origin" required>

        <label for="destination">目的地:</label>
        <input type="text" name="destination" id="destination" required>

        <button type="submit">検索</button>
    </form>
</body>
</html>
