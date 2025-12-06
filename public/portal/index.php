<?php
declare(strict_types=1);

session_start();

require __DIR__ . '/../../config/database.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../../app/';
    if (str_starts_with($class, $prefix)) {
        $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
        $path = $baseDir . $relative . '.php';
        if (file_exists($path)) {
            require $path;
        }
    }
});

use App\Controllers\CustomerPortalController;
use App\Controllers\PrivacyPolicyController;
use App\Helpers\Csrf;
use App\Helpers\InputFilter;
use App\Services\CustomerPortalAuthService;
use App\Services\CustomerPortalService;
use App\Services\NotificationDispatcher;
use App\Services\PrivacyPolicyService;
use App\Services\ProductService;
use App\Services\SalesService;
use App\Services\SystemNotificationService;

$pdo = Database::getConnection();
$notificationsConfig = $GLOBALS['config']['notifications'] ?? [];
$notificationsLog = __DIR__ . '/../../storage/logs/notifications.log';
$notificationDispatcher = new NotificationDispatcher(
    $notificationsConfig['webhook_url'] ?? null,
    is_array($notificationsConfig['webhook_headers'] ?? null) ? $notificationsConfig['webhook_headers'] : [],
    is_array($notificationsConfig['queue'] ?? null) ? $notificationsConfig['queue'] : null,
    $notificationsLog
);
$systemNotificationService = new SystemNotificationService($pdo, $notificationDispatcher, $notificationsLog);
$salesService = new SalesService($pdo);
$productService = new ProductService($pdo);
$authService = new CustomerPortalAuthService($pdo);
$portalService = new CustomerPortalService($pdo, $salesService, $productService, $systemNotificationService);
$privacyPolicyService = new PrivacyPolicyService($pdo);
$portalController = new CustomerPortalController($authService, $portalService);
$privacyPolicyController = new PrivacyPolicyController($privacyPolicyService);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$view = InputFilter::string($_GET['view'] ?? 'dashboard', 32);
if ($view === '') {
    $view = 'dashboard';
}
$feedback = [
    'login' => null,
    'activation' => null,
    'payment' => null,
    'support' => null,
    'password' => null,
    'product' => null,
    'policy' => null,
];
$prefillEmail = InputFilter::email($_GET['prefill_email'] ?? '') ?? '';
$prefillPassword = InputFilter::string($_GET['prefill_password'] ?? '', 120, false, false);

$portalAjaxRequest = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true) && !Csrf::validateRequest($_POST, $_SERVER)) {
    if ($portalAjaxRequest) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(419);
        echo json_encode([
            'success' => false,
            'error' => 'Token CSRF non valido o scaduto.',
        ]);
    } else {
        http_response_code(419);
        echo 'Token CSRF non valido o scaduto. Ricarica la pagina e riprova.';
    }
    exit;
}

if ($method === 'POST') {
    $action = InputFilter::string($_POST['action'] ?? '', 40);
    if ($action === 'login') {
        $result = $portalController->login($_POST);
        if ($result['success']) {
            header('Location: index.php');
            exit;
        }
        $feedback['login'] = $result;
    } elseif ($action === 'complete_invitation') {
        $result = $portalController->completeInvitation($_POST);
        $feedback['activation'] = $result;
        if ($result['success']) {
            $view = 'login';
        }
    } elseif ($action === 'create_payment') {
        $account = $authService->currentAccount();
        if ($account !== null) {
            $result = $portalController->createPaymentRequest($account['id'], $account['customer_id'], $_POST);
            $feedback['payment'] = $result;
        }
    } elseif ($action === 'create_support') {
        $account = $authService->currentAccount();
        if ($account !== null) {
            $result = $portalController->createSupportRequest($account['customer_id'], $account['id'], $_POST);
            $feedback['support'] = $result;
        }
    } elseif ($action === 'create_product_request') {
        $account = $authService->currentAccount();
        if ($account !== null) {
            $result = $portalController->createProductRequest($account['customer_id'], $account['id'], $_POST);
            $feedback['product'] = $result;
            $view = 'sales';
        }
    } elseif ($action === 'update_password') {
        $account = $authService->currentAccount();
        if ($account !== null) {
            $result = $portalController->updatePassword($account['id'], $_POST);
            $feedback['password'] = $result;
        }
    } elseif ($action === 'accept_policy') {
        $account = $authService->currentAccount();
        if ($account !== null) {
            $result = $privacyPolicyController->accept($account['id'], $_POST, $_SERVER);
            $feedback['policy'] = $result;
            if ($result['success']) {
                header('Location: index.php');
                exit;
            }
        } else {
            $feedback['policy'] = [
                'success' => false,
                'message' => 'Autenticazione richiesta.',
                'errors' => ['Accedi per accettare la policy.'],
            ];
        }
        $view = 'privacy';
    } elseif ($action === 'logout') {
        $portalController->logout();
        header('Location: index.php?view=login');
        exit;
    }
}

$account = $authService->currentAccount();

if ($account === null && $view !== 'activate') {
    $view = 'login';
}

$activePolicy = $privacyPolicyService->getActivePolicy();
$requiresPolicyAcceptance = false;
if ($account !== null && $activePolicy !== null) {
    $requiresPolicyAcceptance = !$privacyPolicyService->hasAcceptedPolicy((int) $account['id'], (int) $activePolicy['id']);
    if ($requiresPolicyAcceptance) {
        $view = 'privacy';
    }
}

if ($view === 'privacy' && $activePolicy === null) {
    $activePolicy = $privacyPolicyService->getActivePolicy();
}

switch ($view) {
    case 'activate':
        $token = InputFilter::string($_GET['token'] ?? '', 128);
        portal_render('portal/activate', [
            'token' => $token,
            'feedbackActivation' => $feedback['activation'],
        ], false);
        break;

    case 'login':
        portal_render('portal/login', [
            'feedbackLogin' => $feedback['login'],
            'prefillEmail' => $prefillEmail,
            'prefillPassword' => $prefillPassword,
        ], false);
        break;

    case 'privacy':
        $hasAccepted = false;
        if ($account !== null && $activePolicy !== null) {
            $hasAccepted = !$requiresPolicyAcceptance;
        }
        portal_render('portal/privacy_policy', [
            'policy' => $activePolicy,
            'feedbackPolicy' => $feedback['policy'],
            'account' => $account,
            'hasAccepted' => $hasAccepted,
            'requiresAcceptance' => $account !== null && $activePolicy !== null && !$hasAccepted,
        ], false);
        break;

    default:
        if ($account === null) {
            portal_render('portal/login', [
                'feedbackLogin' => $feedback['login'],
                'prefillEmail' => $prefillEmail,
                'prefillPassword' => $prefillPassword,
            ], false);
            break;
        }

        $profile = $portalService->getAccountProfile($account['id']) ?? [];
        $layoutData = [
            'account' => $account,
            'profile' => $profile,
        ];

        switch ($view) {
            case 'sales':
                $page = InputFilter::int($_GET['page'] ?? 1, 1, PHP_INT_MAX, 1);
                $perPage = InputFilter::int($_GET['per_page'] ?? 10, 1, 20, 10);
                $status = InputFilter::string($_GET['status'] ?? '', 30) ?: null;
                $payment = InputFilter::string($_GET['payment_status'] ?? '', 30) ?: null;
                $sales = $portalService->listSales($account['customer_id'], $page, $perPage, $status, $payment);
                $catalogPage = InputFilter::int($_GET['catalog_page'] ?? 1, 1, PHP_INT_MAX, 1);
                $catalogPerPage = InputFilter::int($_GET['catalog_per_page'] ?? 8, 1, 24, 8);
                $catalogCategory = InputFilter::string($_GET['catalog_category'] ?? '', 60) ?: null;
                $catalogSearch = InputFilter::string($_GET['catalog_search'] ?? '', 120) ?: null;
                $selectedProduct = InputFilter::int($_GET['selected_product'] ?? null, 1, PHP_INT_MAX, 0) ?: null;
                if (($selectedProduct === null || $selectedProduct <= 0) && $feedback['product'] !== null && !($feedback['product']['success'] ?? false)) {
                    $postedProduct = InputFilter::int($_POST['product_id'] ?? 0, 1, PHP_INT_MAX, 0);
                    if ($postedProduct > 0) {
                        $selectedProduct = $postedProduct;
                    }
                }
                $catalog = $portalService->listCatalogProducts($catalogPage, $catalogPerPage, $catalogCategory, $catalogSearch);
                $productOptions = $portalService->listCatalogProductOptions();
                $requests = $portalService->listProductRequests($account['customer_id'], $account['id']);
                $layoutData['view'] = 'sales';
                $layoutData['data'] = [
                    'sales' => $sales['rows'],
                    'pagination' => $sales['pagination'],
                    'filters' => [
                        'status' => $status,
                        'payment_status' => $payment,
                        'per_page' => $perPage,
                    ],
                    'feedbackPayment' => $feedback['payment'],
                    'catalog' => $catalog,
                    'productOptions' => $productOptions,
                    'productRequests' => $requests,
                    'feedbackProduct' => $feedback['product'],
                    'selectedProduct' => $selectedProduct,
                ];
                break;

            case 'orders':
                $statusFilter = InputFilter::string($_GET['status'] ?? '', 30) ?: null;
                $typeFilter = InputFilter::string($_GET['type'] ?? '', 30) ?: null;
                $requests = $portalService->listProductRequests($account['customer_id'], $account['id']);

                $totalValue = 0.0;
                $completedCount = 0;
                $activeCount = 0;
                $lastOrderAt = null;
                foreach ($requests as $request) {
                    $totalValue += (float) ($request['product_price'] ?? 0.0);
                    $statusValue = (string) ($request['status'] ?? 'Pending');
                    if ($statusValue === 'Completed') {
                        $completedCount++;
                    }
                    if (in_array($statusValue, ['Pending', 'InReview', 'Confirmed'], true)) {
                        $activeCount++;
                    }
                    $createdAt = isset($request['created_at']) ? (string) $request['created_at'] : null;
                    if ($createdAt !== null) {
                        $timestamp = strtotime($createdAt);
                        if ($timestamp !== false) {
                            if ($lastOrderAt === null || $timestamp > $lastOrderAt) {
                                $lastOrderAt = $timestamp;
                            }
                        }
                    }
                }

                $filteredRequests = array_filter($requests, static function (array $row) use ($statusFilter, $typeFilter): bool {
                    if ($statusFilter !== null && $statusFilter !== '' && ($row['status'] ?? null) !== $statusFilter) {
                        return false;
                    }
                    if ($typeFilter !== null && $typeFilter !== '' && ($row['request_type'] ?? null) !== $typeFilter) {
                        return false;
                    }

                    return true;
                });

                $layoutData['view'] = 'orders';
                $layoutData['data'] = [
                    'productRequests' => array_values($filteredRequests),
                    'filters' => [
                        'status' => $statusFilter,
                        'type' => $typeFilter,
                    ],
                    'stats' => [
                        'total' => count($requests),
                        'active' => $activeCount,
                        'completed' => $completedCount,
                        'value' => $totalValue,
                        'last_order_at' => $lastOrderAt,
                        'visible' => count($filteredRequests),
                    ],
                ];
                break;

            case 'sale_detail':
                $saleId = InputFilter::int($_GET['sale_id'] ?? 0, 1, PHP_INT_MAX, 0);
                $sale = $saleId > 0 ? $portalService->getSaleDetail($account['customer_id'], $saleId) : null;
                if ($sale === null) {
                    http_response_code(404);
                    portal_render('portal/not_found', [
                        'account' => $account,
                        'profile' => $profile,
                        'message' => 'Vendita non trovata.',
                    ]);
                    break 2;
                }
                $layoutData['view'] = 'sale_detail';
                $layoutData['data'] = [
                    'sale' => $sale,
                    'feedbackPayment' => $feedback['payment'],
                ];
                break;

            case 'payments':
                $page = InputFilter::int($_GET['page'] ?? 1, 1, PHP_INT_MAX, 1);
                $perPage = InputFilter::int($_GET['per_page'] ?? 10, 1, 20, 10);
                $saleStatus = InputFilter::string($_GET['status'] ?? '', 30) ?: null;
                $paymentStatus = InputFilter::string($_GET['payment_status'] ?? '', 30) ?: null;
                $sales = $portalService->listSales($account['customer_id'], $page, $perPage, $saleStatus, $paymentStatus);
                $layoutData['view'] = 'payments';
                $layoutData['data'] = [
                    'sales' => $sales['rows'],
                    'pagination' => $sales['pagination'],
                    'filters' => [
                        'status' => $saleStatus,
                        'payment_status' => $paymentStatus,
                        'per_page' => $perPage,
                    ],
                    'feedbackPayment' => $feedback['payment'],
                ];
                break;

            case 'support_detail':
                $requestId = InputFilter::int($_GET['request_id'] ?? 0, 1, PHP_INT_MAX, 0);
                $request = $requestId > 0 ? $portalService->getSupportRequest($account['customer_id'], $account['id'], $requestId) : null;
                if ($request === null) {
                    http_response_code(404);
                    portal_render('portal/not_found', [
                        'account' => $account,
                        'profile' => $profile,
                        'message' => 'Richiesta non trovata.',
                    ]);
                    break 2;
                }
                $layoutData['view'] = 'support_detail';
                $layoutData['data'] = [
                    'request' => $request,
                ];
                break;

            case 'support':
                $page = InputFilter::int($_GET['page'] ?? 1, 1, PHP_INT_MAX, 1);
                $perPage = InputFilter::int($_GET['per_page'] ?? 10, 1, 20, 10);
                $status = InputFilter::string($_GET['status'] ?? '', 30) ?: null;
                $requests = $portalService->listSupportRequests($account['customer_id'], $account['id'], $page, $perPage, $status);
                $layoutData['view'] = 'support';
                $layoutData['data'] = [
                    'requests' => $requests['rows'],
                    'pagination' => $requests['pagination'],
                    'filters' => [
                        'status' => $status,
                        'per_page' => $perPage,
                    ],
                    'feedbackSupport' => $feedback['support'],
                ];
                break;

            case 'settings':
                $layoutData['view'] = 'settings';
                $layoutData['data'] = [
                    'feedbackPassword' => $feedback['password'],
                ];
                break;

            case 'dashboard':
            default:
                $dashboard = $portalService->getDashboard($account['customer_id'], $account['id']);
                $layoutData['view'] = 'dashboard';
                $layoutData['data'] = $dashboard;
                break;
        }

        portal_render('portal/layout', $layoutData);
        break;
}

/**
 * @param array<string, mixed> $params
 */
function portal_render(string $template, array $params = [], bool $useLayout = true): void
{
    $viewPath = __DIR__ . '/../../views/' . $template . '.php';
    if (!file_exists($viewPath)) {
        throw new \RuntimeException('View non trovata: ' . $template);
    }

    extract($params, EXTR_SKIP);

    if ($useLayout) {
        ob_start();
        require $viewPath;
        $content = Csrf::injectIntoForms(ob_get_clean() ?: '');

        ob_start();
        require __DIR__ . '/../../views/portal/master.php';
        $layoutOutput = ob_get_clean();
        echo Csrf::injectIntoForms($layoutOutput ?: '');
    } else {
        ob_start();
        require $viewPath;
        $viewOutput = ob_get_clean();
        echo Csrf::injectIntoForms($viewOutput ?: '');
    }
}

function portal_badge_class(string $status): string
{
    return match ($status) {
        'Paid' => 'success',
        'Partial' => 'warning',
        'Pending' => 'muted',
        'Overdue' => 'danger',
        'Succeeded' => 'success',
        'Failed' => 'danger',
        'Open' => 'warning',
        'InProgress' => 'info',
        'Completed' => 'success',
        'Cancelled' => 'muted',
        'InReview' => 'info',
        'Confirmed' => 'success',
        'Declined' => 'danger',
        default => 'muted',
    };
}

/**
 * @param array<string, mixed> $filters
 */
function portal_paginate_link(string $view, int $page, array $filters = []): string
{
    $params = ['view' => $view, 'page' => max(1, $page)];
    foreach (['status', 'payment_status', 'per_page', 'status_filter', 'catalog_page', 'catalog_per_page', 'catalog_category', 'catalog_search', 'selected_product'] as $key) {
        if (isset($filters[$key]) && $filters[$key] !== null && $filters[$key] !== '') {
            $params[$key] = $filters[$key];
        }
    }

    return 'index.php?' . http_build_query($params);
}
