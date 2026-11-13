<?php
// app/Controllers/WelcomeController.php
// Renders the public welcome (role selection) and download pages.
// Both routes are marked 'auth' => false in the router.

class WelcomeController extends Controller
{
    public function index(): void
    {
        require __DIR__ . '/../Views/welcome/index.php';
    }

    public function download(): void
    {
        require __DIR__ . '/../Views/welcome/download.php';
    }
}
