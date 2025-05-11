<?php
namespace Assets;

require_once __DIR__ . '/../../../../app/config.php';
require_once __DIR__ . '/../../../../app/assets/api/db_connect.php';
require_once __DIR__ . '/../../../../app/assets/api/public_api/public_api.php';
require_once __DIR__ . '/../../../../app/assets/api/user_apis/apis/users/users.php';

use Database\Database;
use Api\PublicAPI\PublicAPI;
use Api\UserAPI\UserMenagment;

class LayoutRenderer
{
    private \PDO $conn;
    private array $institution;
    private bool $isLoggedIn;
    private bool $isAdmin;
    private ?UserMenagment $userApi;

    public function __construct(\PDO $connection)
    {
        $this->conn = $connection;
        $this->fetchInstitution();
        $this->initUserState();
    }

    private function fetchInstitution(): void
    {
        $api = new PublicAPI($this->conn);
        $res = $api->getInstitution();
        if (!$res['success']) {
            throw new \RuntimeException("Error fetching institution: " . $res['error']);
        }
        $this->institution = $res['institution'];
    }

    private function initUserState(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->isLoggedIn = !empty($_SESSION['userID']);
        $this->isAdmin    = false;
        $this->userApi    = null;

        if ($this->isLoggedIn) {
            $this->userApi = new UserMenagment($_SESSION['userID'], $this->conn);
            $perm = $this->userApi->getUserPermissions();
            $this->isAdmin = ($perm['success'] && (($perm['permissions']['name'] ?? '') === 'Administrator'));
        }
    }

    public function renderHead(): void
    {
        $logo = htmlspecialchars(UPLOADS_FOLDER . 'institutions/' . $this->institution['favicon_url'] ?? '');

        echo '<meta charset="utf-8">' . PHP_EOL;
        echo '<meta name="viewport" content="width=device-width, initial-scale=1">' . PHP_EOL;

        echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . PHP_EOL;
        echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . PHP_EOL;
        echo '<link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">' . PHP_EOL;

        echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">' . PHP_EOL;
        echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">' . PHP_EOL;
        echo '<script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>' . PHP_EOL;

        echo '<link rel="stylesheet" href="' . HTTP_ADRESS . 'assets/css/basic.css">' . PHP_EOL;
        echo '<script defer src="' . HTTP_ADRESS . 'assets/js/nav.js"></script>' . PHP_EOL;

        if ($this->institution['favicon_url']) {
            echo '<link rel="icon" href="' . $logo . '" type="image/png">' . PHP_EOL;
        }
    }

    public function renderNav(): void
    {
        $logo = htmlspecialchars($this->institution['logo_url'] ?? '');
        $name = htmlspecialchars($this->institution['name'] ?? '');

        echo '<nav class="navbar navbar-expand-lg fixed-top bg-body shadow-sm" id="nav"><div class="container">' . PHP_EOL;
        echo '<a class="navbar-brand d-flex align-items-center" href="' . HTTP_ADRESS . '">' . PHP_EOL;
        if ($logo) {
            echo '<img src="' . UPLOADS_FOLDER . 'institutions/' . $logo . '" alt="Logo ' . $name . '" class="me-2 rounded" style="height:30px; object-fit:contain; border-radius:0.25rem;">' . PHP_EOL;
        } else {
            echo '<i class="bi bi-house-fill me-2"></i>' . PHP_EOL;
        }
        echo '</a>' . PHP_EOL;

        echo '<button class="navbar-toggler d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#navOffcanvas" aria-controls="navOffcanvas"><span class="navbar-toggler-icon"></span></button>' . PHP_EOL;
        echo '<div class="d-none d-lg-flex justify-content-end"><ul class="navbar-nav">' . PHP_EOL;

        if ($this->isLoggedIn) {
            echo '<li class="nav-item"><a class="nav-link" href="' . MY_CERTIFICATES_PAGE . '">My Certificates</a></li>' . PHP_EOL;
            echo '<li class="nav-item"><a class="nav-link" href="' . VERIFY_CERTIFICATE_PAGE . '">Verify Certificate</a></li>' . PHP_EOL;
            if ($this->isAdmin) {
                echo '<li class="nav-item"><a class="nav-link" href="' . ADMIN_PANEL . '">Admin Panel</a></li>' . PHP_EOL;
            }
            echo '<li class="nav-item"><a class="nav-link" href="' . MY_ACCOUNT_PAGE . '">Account</a></li>' . PHP_EOL;
            echo '<li class="nav-item ms-lg-3"><a class="nav-link text-secondary" href="' . LOGOUT_SCRIPT . '"><i class="bi bi-box-arrow-right me-1"></i>Logout</a></li>' . PHP_EOL;
        } else {
            echo '<li class="nav-item"><a class="nav-link" href="' . VERIFY_CERTIFICATE_PAGE . '">Verify Certificate</a></li>' . PHP_EOL;
            echo '<li class="nav-item ms-lg-3"><a class="nav-link" href="' . LOGIN_PAGE . '"><i class="bi bi-box-arrow-in-right me-1"></i>Login</a></li>' . PHP_EOL;
        }

        echo '</ul></div>' . PHP_EOL;

        echo '<div class="offcanvas offcanvas-start d-lg-none" tabindex="-1" id="navOffcanvas"><div class="offcanvas-header">' . PHP_EOL;
        if ($logo) {
            echo '<img src="' . UPLOADS_FOLDER . 'institutions/' . $logo . '" alt="Logo ' . $name . '" class="me-2 rounded" style="height:30px; object-fit:contain; border-radius:0.25rem;">' . PHP_EOL;
        }
        echo '<button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button></div><div class="offcanvas-body"><ul class="navbar-nav">' . PHP_EOL;

        if ($this->isLoggedIn) {
            echo '<li class="nav-item"><a class="nav-link" href="' . MY_CERTIFICATES_PAGE . '">My Certificates</a></li>' . PHP_EOL;
            echo '<li class="nav-item"><a class="nav-link" href="' . VERIFY_CERTIFICATE_PAGE . '">Verify Certificate</a></li>' . PHP_EOL;
            if ($this->isAdmin) {
                echo '<li class="nav-item"><a class="nav-link" href="' . ADMIN_PANEL . '">Admin Panel</a></li>' . PHP_EOL;
            }
            echo '<li class="nav-item"><a class="nav-link" href="' . MY_ACCOUNT_PAGE . '">Account</a></li>' . PHP_EOL;
            echo '<li class="nav-item mt-2"><a class="nav-link text-secondary" href="' . LOGOUT_SCRIPT . '"><i class="bi bi-box-arrow-right me-1"></i>Logout</a></li>' . PHP_EOL;
        } else {
            echo '<li class="nav-item"><a class="nav-link" href="' . VERIFY_CERTIFICATE_PAGE . '">Verify Certificate</a></li>' . PHP_EOL;
            echo '<li class="nav-item mt-2"><a class="nav-link" href="' . LOGIN_PAGE . '"><i class="bi bi-box-arrow-in-right me-1"></i>Login</a></li>' . PHP_EOL;
        }

        echo '</ul></div></div></div></nav>' . PHP_EOL;

        echo '<button id="theme-toggle" class="btn btn-outline-secondary position-fixed rounded-circle shadow" style="width:50px;height:50px;bottom:20px;right:20px;z-index:1030;">';
        echo '<i class="bi" id="theme-icon"></i>';
        echo '</button>'.PHP_EOL;
    }

    public function renderMinNav(): void
    {
        $logo = htmlspecialchars($this->institution['logo_url'] ?? '');

        echo '<nav class="navbar fixed-top bg-body shadow-sm"><div class="container d-flex align-items-center">' . PHP_EOL;
        echo '<a class="navbar-brand d-flex align-items-center" href="' . HTTP_ADRESS . '">' . PHP_EOL;
        if ($logo) {
            echo '<img src="' . UPLOADS_FOLDER . 'institutions/' . $logo . '" alt="Logo" class="me-2 rounded" style="height:40px; object-fit:contain;">' . PHP_EOL;
        } else {
            echo '<i class="bi bi-house-fill me-1"></i>' . PHP_EOL;
        }
        echo '</a></div></nav>' . PHP_EOL;
    }

    public function renderFooter(): void
    {
        $logo    = htmlspecialchars($this->institution['logo_url'] ?? '');
        $name    = htmlspecialchars($this->institution['name'] ?? '');
        $tagline = htmlspecialchars($this->institution['tagline'] ?? '');

        echo '<footer id="footer" class="bg-body-tertiary text-body-secondary py-5 mt-5"><div class="container"><div class="row">' . PHP_EOL;
        echo '<div class="col-md-4 mb-4 text-center text-md-start"><a href="' . HTTP_ADRESS . '">' . PHP_EOL;
        if ($logo) {
            echo '<img src="' . UPLOADS_FOLDER . 'institutions/' . $logo . '" alt="' . $name . ' logo" class="rounded" style="height:40px; object-fit:contain;">' . PHP_EOL;
        } else {
            echo '<i class="bi bi-building h2"></i>' . PHP_EOL;
        }
        echo '</a>' . PHP_EOL;
        if ($tagline) {
            echo '<p class="small fst-italic mt-2">' . $tagline . '</p>' . PHP_EOL;
        }
        echo '</div><div class="col-md-4 mb-4"><h6 class="text-uppercase mb-3">PUBLIC INFO</h6><ul class="list-unstyled">' . PHP_EOL;
        echo '<li><a href="' . VERIFY_CERTIFICATE_PAGE . '" class="text-body-secondary text-decoration-none">Verify Certificate</a></li>' . PHP_EOL;
        echo '<li><a href="' . TERMS_PAGE . '" target="_blank" class="text-body-secondary text-decoration-none">Terms & Conditions</a></li>' . PHP_EOL;
        echo '</ul></div>' . PHP_EOL;

        if ($this->isLoggedIn) {
            echo '<div class="col-md-4 mb-4"><h6 class="text-uppercase mb-3">USER\'S INFO</h6><ul class="list-unstyled">' . PHP_EOL;
            echo '<li><a href="' . MY_CERTIFICATES_PAGE . '" class="text-body-secondary text-decoration-none">My Certificates</a></li>' . PHP_EOL;
            echo '<li><a href="' . MY_ACCOUNT_PAGE . '" class="text-body-secondary text-decoration-none">Profile Settings</a></li>' . PHP_EOL;
            if ($this->isAdmin) {
                echo '<li><a href="' . ADMIN_PANEL . '" class="text-body-secondary text-decoration-none">Admin Dashboard</a></li>' . PHP_EOL;
            }
            echo '</ul></div>' . PHP_EOL;
        }

        echo '</div><div class="border-top pt-3 text-center small">&copy; ' . date('Y') . ' All rights reserved.</div>' . PHP_EOL;
        echo '</div></footer>' . PHP_EOL;
    }
}
