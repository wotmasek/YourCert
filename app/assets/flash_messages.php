<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function setFlashMessage(string $type, string $message): void {
    $_SESSION['flash_messages'][$type][] = $message;
}

function getFlashMessages(): string {
    $html = '';
    if (empty($_SESSION['flash_messages'])) {
        return '';
    }

    foreach ($_SESSION['flash_messages'] as $msgType => $messages) {
        switch ($msgType) {
            case 'error':
                $bsClass = 'danger';
                break;
            case 'neutral':
                $bsClass = 'info';
                break;
            case 'success':
                $bsClass = 'success';
                break;
            default:
                $bsClass = 'secondary';
        }
        foreach ((array)$messages as $msg) {
            $html .= <<<HTML
            <div class="alert alert-{$bsClass} alert-dismissible fade show" role="alert">
                {$msg}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Zamknij"></button>
            </div>
            HTML;
        }
    }

    unset($_SESSION['flash_messages']);
    return $html;
}
?>
