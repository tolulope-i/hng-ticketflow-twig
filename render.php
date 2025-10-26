<?php
require_once 'vendor/autoload.php';

$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/templates');
$twig = new \Twig\Environment($loader, [
    'cache' => false,
]);

// List all the pages you want to convert
$pages = [
    'base.html.twig',
    'dashboard.html.twig',
    'landing.html.twig',
    'login.html.twig',
    'signup.html.twig',
    'tickets.html.twig',
    // Add more if needed
];

// Create dist folder if not exists
if (!is_dir('dist')) {
    mkdir('dist');
}

// Render each page to HTML
foreach ($pages as $page) {
    $output = $twig->render($page);

    // Replace .html.twig with .html
    $outputFile = 'dist/' . str_replace('.html.twig', '.html', $page);
    file_put_contents($outputFile, $output);
    echo "Rendered: $outputFile\n";
}
