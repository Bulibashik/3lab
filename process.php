<?php
header('Content-Type: text/html; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html');
    exit();
}

$errors = [];

$full_name = trim($_POST['full_name'] ?? '');
if (empty($full_name)) {
    $errors[] = 'ФИО обязательно.';
} elseif (strlen($full_name) > 150) {
    $errors[] = 'ФИО не более 150 символов.';
} elseif (!preg_match('/^[а-яА-ЯёЁa-zA-Z\s\-]+$/u', $full_name)) {
    $errors[] = 'ФИО только буквы, пробелы, дефис.';
}

$phone = trim($_POST['phone'] ?? '');
if (empty($phone)) {
    $errors[] = 'Телефон обязателен.';
} elseif (!preg_match('/^[\+\d][\d\(\)\-\s]{5,20}$/', $phone)) {
    $errors[] = 'Некорректный телефон.';
}

$email = trim($_POST['email'] ?? '');
if (empty($email)) {
    $errors[] = 'Email обязателен.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Некорректный email.';
}

$birth_date = $_POST['birth_date'] ?? '';
if (empty($birth_date)) {
    $errors[] = 'Дата рождения обязательна.';
} elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birth_date)) {
    $errors[] = 'Неверный формат даты.';
}

$allowed_genders = ['male', 'female', 'other'];
$gender = $_POST['gender'] ?? '';
if (!in_array($gender, $allowed_genders)) {
    $errors[] = 'Выберите пол.';
}

$allowed_languages = ['Pascal','C','C++','JavaScript','PHP','Python','Java','Haskell','Clojure','Prolog','Scala','Go'];
$languages = $_POST['languages'] ?? [];
if (empty($languages)) {
    $errors[] = 'Выберите хотя бы один язык.';
} else {
    foreach ($languages as $lang) {
        if (!in_array($lang, $allowed_languages)) {
            $errors[] = 'Недопустимый язык.';
            break;
        }
    }
}

$agreed = isset($_POST['agreed']) && $_POST['agreed'] === 'on';
if (!$agreed) {
    $errors[] = 'Примите условия контракта.';
}

$biography = trim($_POST['biography'] ?? '');

if (!empty($errors)) {
    echo '<!DOCTYPE html><html><head><style>body{font-family:Arial;padding:30px;background:#f0f2f5}.container{max-width:600px;margin:auto;background:white;padding:30px;border-radius:15px}.error{color:red;background:#ffe0e0;padding:10px;border-radius:5px}</style></head><body><div class="container"><h1>Ошибки</h1><div class="error">';
    foreach ($errors as $e) echo "• $e<br>";
    echo '</div><a href="index.html">← Назад</a></div></body></html>';
    exit();
}

try {
    $pdo = new PDO('mysql:host=localhost;dbname=u82318;charset=utf8', 'u82318', '5918027', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("INSERT INTO users (full_name, phone, email, birth_date, gender, biography, agreed) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$full_name, $phone, $email, $birth_date, $gender, $biography, (int)$agreed]);
    $user_id = $pdo->lastInsertId();
    
    $stmt = $pdo->prepare("SELECT id FROM languages WHERE name = ?");
    $stmt2 = $pdo->prepare("INSERT INTO user_languages (user_id, language_id) VALUES (?, ?)");
    foreach ($languages as $lang) {
        $stmt->execute([$lang]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) $stmt2->execute([$user_id, $row['id']]);
    }
    
    $pdo->commit();
    
    echo '<!DOCTYPE html><html><head><style>body{font-family:Arial;padding:30px;background:#f0f2f5}.container{max-width:600px;margin:auto;background:white;padding:30px;border-radius:15px}.success{color:green;background:#e0ffe0;padding:10px;border-radius:5px}</style></head><body><div class="container"><h1>✅ Успешно!</h1><div class="success">Данные сохранены! Ваш ID: ' . $user_id . '</div><a href="index.html">← Заполнить ещё</a></div></body></html>';
    
} catch (PDOException $e) {
    $pdo->rollBack();
    echo '<!DOCTYPE html><html><head><style>body{font-family:Arial;padding:30px;background:#f0f2f5}.container{max-width:600px;margin:auto;background:white;padding:30px;border-radius:15px}.error{color:red;background:#ffe0e0;padding:10px;border-radius:5px}</style></head><body><div class="container"><h1>Ошибка БД</h1><div class="error">' . htmlspecialchars($e->getMessage()) . '</div><a href="index.html">← Назад</a></div></body></html>';
}
?>
