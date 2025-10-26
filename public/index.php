<?php
require_once '../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

session_start();

// Initialize Twig
$loader = new FilesystemLoader('../templates');
$twig = new Environment($loader, [
    'cache' => false, // disable cache for development
    'debug' => true
]);


// Initialize demo data
function initializeDemoData() {
    if (!isset($_SESSION['ticketapp_users'])) {
        $_SESSION['ticketapp_users'] = [
            [
                'id' => 1,
                'name' => 'Demo User',
                'email' => 'demo@ticketflow.com',
                'password' => 'demo123'
            ],
            [
                'id' => 2,
                'name' => 'Dev Tolu',
                'email' => 'tolu@gmail.com',
                'password' => 'tolu123'
            ]
        ];
    }
    
    if (!isset($_SESSION['ticketapp_tickets'])) {
        $_SESSION['ticketapp_tickets'] = [];
    }
}

// Authentication helper functions
function isAuthenticated() {
    return isset($_SESSION['user']);
}

function requireAuth() {
    if (!isAuthenticated()) {
        return new RedirectResponse('/auth/login');
    }
    return null;
}

function loginUser($email, $password) {
    initializeDemoData();
    $users = $_SESSION['ticketapp_users'];
    
    foreach ($users as $user) {
        if ($user['email'] === $email && $user['password'] === $password) {
            $_SESSION['user'] = [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email']
            ];
            return true;
        }
    }
    return false;
}

function registerUser($name, $email, $password) {
    initializeDemoData();
    $users = $_SESSION['ticketapp_users'];
    
    // Check if user already exists
    foreach ($users as $user) {
        if ($user['email'] === $email) {
            return ['success' => false, 'error' => 'User with this email already exists'];
        }
    }
    
    // Validate input
    if (empty($name) || empty($email) || empty($password)) {
        return ['success' => false, 'error' => 'Please fill all fields'];
    }
    
    if (strlen($password) < 6) {
        return ['success' => false, 'error' => 'Password must be at least 6 characters'];
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Please enter a valid email address'];
    }
    
    // Create new user
    $newUser = [
        'id' => count($users) + 1,
        'name' => $name,
        'email' => $email,
        'password' => $password,
        'createdAt' => date('c')
    ];
    
    $_SESSION['ticketapp_users'][] = $newUser;
    
    $_SESSION['user'] = [
        'id' => $newUser['id'],
        'name' => $newUser['name'],
        'email' => $newUser['email']
    ];
    
    return ['success' => true];
}

function logoutUser() {
    unset($_SESSION['user']);
}

// Define routes
$routes = new RouteCollection();

$routes->add('index', new Route('/', [
    '_controller' => function() use ($twig) {
        $user = $_SESSION['user'] ?? null;
        return new Response($twig->render('index.html.twig', [
            'user' => $user
        ]));
    }
]));


$routes->add('login', new Route('/auth/login', [
    '_controller' => function(Request $request) use ($twig) {
        if (isAuthenticated()) {
            return new RedirectResponse('/dashboard');
        }
        
        $error = '';
        if ($request->getMethod() === 'POST') {
            $email = $request->request->get('email');
            $password = $request->request->get('password');
            
            if (loginUser($email, $password)) {
                return new RedirectResponse('/dashboard');
            } else {
                $error = 'Invalid email or password';
            }
        }
        
        return new Response($twig->render('login.html.twig', ['error' => $error]));
    }
]));

$routes->add('signup', new Route('/auth/signup', [
    '_controller' => function(Request $request) use ($twig) {
        if (isAuthenticated()) {
            return new RedirectResponse('/dashboard');
        }
        
        $error = '';
        if ($request->getMethod() === 'POST') {
            $name = $request->request->get('name');
            $email = $request->request->get('email');
            $password = $request->request->get('password');
            $confirmPassword = $request->request->get('confirmPassword');
            
            if ($password !== $confirmPassword) {
                $error = 'Passwords do not match';
            } else {
                $result = registerUser($name, $email, $password);
                if ($result['success']) {
                    return new RedirectResponse('/dashboard');
                } else {
                    $error = $result['error'];
                }
            }
        }
        
        return new Response($twig->render('signup.html.twig', ['error' => $error]));
    }
]));

$routes->add('dashboard', new Route('/dashboard', [
    '_controller' => function() use ($twig) {
        $authCheck = requireAuth();
        if ($authCheck) return $authCheck;
        
        initializeDemoData();
        $tickets = $_SESSION['ticketapp_tickets'];
        
        $stats = [
            'total' => count($tickets),
            'open' => count(array_filter($tickets, fn($t) => $t['status'] === 'open')),
            'inProgress' => count(array_filter($tickets, fn($t) => $t['status'] === 'in_progress')),
            'closed' => count(array_filter($tickets, fn($t) => $t['status'] === 'closed'))
        ];
        
        return new Response($twig->render('dashboard.html.twig', [
            'user' => $_SESSION['user'],
            'stats' => $stats
        ]));
    }
]));

$routes->add('tickets', new Route('/tickets', [
    '_controller' => function(Request $request) use ($twig) {
        $authCheck = requireAuth();
        if ($authCheck) return $authCheck;
        
        initializeDemoData();
        $tickets = $_SESSION['ticketapp_tickets'];
        
        // Handle form submissions
        if ($request->getMethod() === 'POST') {
            $action = $request->request->get('action');
            
            if ($action === 'create' || $action === 'update') {
                $title = $request->request->get('title');
                $description = $request->request->get('description');
                $status = $request->request->get('status');
                $priority = $request->request->get('priority');
                
                // Validate
                if (empty($title) || empty($status)) {
                    $_SESSION['error'] = 'Title and status are required';
                } else {
                    if ($action === 'create') {
                        $newTicket = [
                            'id' => count($tickets) + 1,
                            'title' => $title,
                            'description' => $description,
                            'status' => $status,
                            'priority' => $priority,
                            'createdBy' => $_SESSION['user']['email'],
                            'createdAt' => date('c'),
                            'updatedAt' => date('c')
                        ];
                        $tickets[] = $newTicket;
                        $_SESSION['success'] = 'Ticket created successfully!';
                    } else {
                        $ticketId = $request->request->get('ticket_id');
                        foreach ($tickets as &$ticket) {
                            if ($ticket['id'] == $ticketId) {
                                $ticket['title'] = $title;
                                $ticket['description'] = $description;
                                $ticket['status'] = $status;
                                $ticket['priority'] = $priority;
                                $ticket['updatedAt'] = date('c');
                                $_SESSION['success'] = 'Ticket updated successfully!';
                                break;
                            }
                        }
                    }
                    $_SESSION['ticketapp_tickets'] = $tickets;
                }
            } elseif ($action === 'delete') {
                $ticketId = $request->request->get('ticket_id');
                $tickets = array_filter($tickets, fn($t) => $t['id'] != $ticketId);
                $_SESSION['ticketapp_tickets'] = array_values($tickets);
                $_SESSION['success'] = 'Ticket deleted successfully!';
            }
            
            return new RedirectResponse('/tickets');
        }
        
        $success = $_SESSION['success'] ?? '';
        $error = $_SESSION['error'] ?? '';
        unset($_SESSION['success'], $_SESSION['error']);
        
        return new Response($twig->render('tickets.html.twig', [
            'user' => $_SESSION['user'],
            'tickets' => $tickets,
            'success' => $success,
            'error' => $error
        ]));
    }
]));

$routes->add('logout', new Route('/logout', [
    '_controller' => function() {
        logoutUser();
        return new RedirectResponse('/');
    }
]));

// Route the request
$request = Request::createFromGlobals();
$context = new RequestContext();
$context->fromRequest($request);
$matcher = new UrlMatcher($routes, $context);

try {
    $parameters = $matcher->match($request->getPathInfo());
    $response = call_user_func($parameters['_controller'], $request);
} catch (Exception $e) {
    $response = new Response('Page not found', 404);
}

$response->send();