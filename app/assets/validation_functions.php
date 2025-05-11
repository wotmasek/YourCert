<?php

function validateLogin($login) {
    $login = trim($login);
    if (mb_strlen($login) < 3 || mb_strlen($login) > 20) {
        return false;
    }
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $login)) {
        return false;
    }
    return true;
}

function validatePassword($password, $minLength = 8) {
    if (mb_strlen($password) < $minLength) {
        return false;
    }
    if (!preg_match('/[a-z]/', $password)) {
        return false;
    }
    if (!preg_match('/[\W_]/', $password)) {
        return false;
    }
    return true;
}

function validateName($name, $minLength = 2, $maxLength = 50) {
    $name = trim($name);
    if (mb_strlen($name) < $minLength || mb_strlen($name) > $maxLength) {
        return false;
    }
    if (!preg_match('/^[A-ZĄĆĘŁŃÓŚŹŻ][a-ząćęłńóśźż]+([ -][A-ZĄĆĘŁŃÓŚŹŻ][a-ząćęłńóśźż]+)*$/u', $name)) {
        return false;
    }
    return true;
}

function validate_birth_date($birth_date, $minAge = 3, $maxAge = 125) {
    $d = DateTime::createFromFormat('Y-m-d', $birth_date);
    if (!($d && $d->format('Y-m-d') === $birth_date)) {
        return false;
    }
    $today = new DateTime();
    $age = $today->diff($d)->y;
    return ($age >= $minAge && $age <= $maxAge);
}

function validateInt($value, $min = null, $max = null) {
    if (filter_var($value, FILTER_VALIDATE_INT) === false) {
        return false;
    }
    $intValue = (int)$value;
    if ($min !== null && $intValue < $min) {
        return false;
    }
    if ($max !== null && $intValue > $max) {
        return false;
    }
    return true;
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validateToken($token, $expectedLength = 32) {
    if (!is_string($token) || strlen($token) !== $expectedLength) {
        return false;
    }
    if (!ctype_xdigit($token)) {
        return false;
    }
    return true;
}

function validateDateFormat($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return ($d && $d->format($format) === $date);
}

function validateUrl($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

function validatePhoneNumber(string $phone, int $minDigits = 7, int $maxDigits = 15): bool {
    $digits = preg_replace('/\D+/', '', $phone);
    if ($digits === null) {
        return false;
    }
    $length = strlen($digits);
    return ($length >= $minDigits && $length <= $maxDigits);
}

function validateText($text, $maxLength = 255) {
    if (!is_string($text)) {
        return false;
    }
    return mb_strlen($text) <= $maxLength;
}
?>
