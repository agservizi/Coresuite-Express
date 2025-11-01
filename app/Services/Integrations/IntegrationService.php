<?php
declare(strict_types=1);

namespace App\Services\Integrations;

use App\Services\Integrations\Coresuite\CoresuiteClient;
use App\Services\Integrations\DigitalSignature\DigitalSignatureClient;
use App\Services\Integrations\Payments\PaymentGatewayClient;
use App\Services\Integrations\Ticketing\TicketingClient;
use Throwable;

final class IntegrationService
{
    public function __construct(
        private readonly ?CoresuiteClient $coresuiteClient,
        private readonly ?PaymentGatewayClient $paymentGatewayClient,
        private readonly ?TicketingClient $ticketingClient,
        private readonly ?DigitalSignatureClient $digitalSignatureClient,
        private readonly IntegrationLogger $logger
    ) {
    }

    /**
     * @param array<string,mixed> $customer
     */
    public function syncCustomer(array $customer): void
    {
        if ($this->coresuiteClient === null) {
            return;
        }

        try {
            $payload = $this->buildCustomerPayload($customer);
            $this->coresuiteClient->upsertCustomer($payload);
        } catch (Throwable $exception) {
            $this->logger->exception($exception, ['operation' => 'customer_sync']);
        }
    }

    public function removeCustomer(int $customerId): void
    {
        if ($this->coresuiteClient === null) {
            return;
        }

        try {
            $this->coresuiteClient->deleteCustomer('customer-' . $customerId);
        } catch (Throwable $exception) {
            $this->logger->exception($exception, ['operation' => 'customer_delete']);
        }
    }

    /**
     * @param array<string,mixed> $product
     */
    public function syncProduct(array $product): void
    {
        if ($this->coresuiteClient === null) {
            return;
        }

        try {
            $payload = $this->buildProductPayload($product);
            $this->coresuiteClient->upsertProduct($payload);
        } catch (Throwable $exception) {
            $this->logger->exception($exception, ['operation' => 'product_sync']);
        }
    }

    /**
     * @param array<string,mixed> $sale
     * @param array<int,array<string,mixed>> $items
     */
    public function syncSale(array $sale, array $items): void
    {
        if ($this->coresuiteClient === null) {
            return;
        }

        try {
            $payload = $this->buildSalePayload($sale, $items);
            $this->coresuiteClient->pushSale($payload);
        } catch (Throwable $exception) {
            $this->logger->exception($exception, ['operation' => 'sale_sync']);
        }
    }

    /**
     * @param array<string,mixed> $movement
     */
    public function syncInventoryMovement(array $movement): void
    {
        if ($this->coresuiteClient === null) {
            return;
        }

        try {
            $this->coresuiteClient->pushInventoryAdjustment($movement);
        } catch (Throwable $exception) {
            $this->logger->exception($exception, ['operation' => 'inventory_sync']);
        }
    }

    /**
     * @param array<string,mixed> $paymentData
     */
    public function forwardPayment(array $paymentData): void
    {
        if ($this->paymentGatewayClient === null || !$this->paymentGatewayClient->isEnabled()) {
            return;
        }

        try {
            $this->paymentGatewayClient->capturePayment($paymentData);
        } catch (Throwable $exception) {
            $this->logger->exception($exception, ['operation' => 'payment_forward']);
        }
    }

    /**
     * @param array<string,mixed> $payment
     */
    public function createPaymentIntent(array $payment): void
    {
        if ($this->paymentGatewayClient === null || !$this->paymentGatewayClient->isEnabled()) {
            return;
        }

        try {
            $this->paymentGatewayClient->createPaymentIntent($payment);
        } catch (Throwable $exception) {
            $this->logger->exception($exception, ['operation' => 'payment_intent']);
        }
    }

    /**
     * @param array<string,mixed> $ticket
     */
    public function pushSupportTicket(array $ticket): void
    {
        if ($this->ticketingClient === null || !$this->ticketingClient->isEnabled()) {
            return;
        }

        try {
            $this->ticketingClient->createTicket($ticket);
        } catch (Throwable $exception) {
            $this->logger->exception($exception, ['operation' => 'ticket_push']);
        }
    }

    /**
     * @param array<string,mixed> $document
     */
    public function requestSignature(array $document): void
    {
        if ($this->digitalSignatureClient === null || !$this->digitalSignatureClient->isEnabled()) {
            return;
        }

        try {
            $this->digitalSignatureClient->sendSignatureRequest($document);
        } catch (Throwable $exception) {
            $this->logger->exception($exception, ['operation' => 'signature_request']);
        }
    }

    /**
     * @param array<string,mixed> $customer
     * @return array<string,mixed>
     */
    private function buildCustomerPayload(array $customer): array
    {
        return [
            'external_id' => 'customer-' . (int) ($customer['id'] ?? 0),
            'full_name' => (string) ($customer['fullname'] ?? ''),
            'email' => $customer['email'] ?? null,
            'phone' => $customer['phone'] ?? null,
            'tax_code' => $customer['tax_code'] ?? null,
            'note' => $customer['note'] ?? null,
            'synced_at' => date('c'),
        ];
    }

    /**
     * @param array<string,mixed> $product
     * @return array<string,mixed>
     */
    private function buildProductPayload(array $product): array
    {
        return [
            'external_id' => 'product-' . (int) ($product['id'] ?? 0),
            'name' => (string) ($product['name'] ?? ''),
            'sku' => $product['sku'] ?? null,
            'imei' => $product['imei'] ?? null,
            'category' => $product['category'] ?? null,
            'price' => isset($product['price']) ? (float) $product['price'] : null,
            'stock_quantity' => isset($product['stock_quantity']) ? (int) $product['stock_quantity'] : null,
            'tax_rate' => isset($product['tax_rate']) ? (float) $product['tax_rate'] : null,
            'vat_code' => $product['vat_code'] ?? null,
            'is_active' => ((int) ($product['is_active'] ?? 1)) === 1,
            'synced_at' => date('c'),
        ];
    }

    /**
     * @param array<string,mixed> $sale
     * @param array<int,array<string,mixed>> $items
     * @return array<string,mixed>
     */
    private function buildSalePayload(array $sale, array $items): array
    {
        $normalizedItems = [];
        foreach ($items as $item) {
            $normalizedItems[] = [
                'description' => $item['description'] ?? null,
                'quantity' => isset($item['quantity']) ? (int) $item['quantity'] : 1,
                'unit_price' => isset($item['price']) ? (float) $item['price'] : 0.0,
                'tax_rate' => isset($item['tax_rate']) ? (float) $item['tax_rate'] : 0.0,
                'tax_amount' => isset($item['tax_amount']) ? (float) $item['tax_amount'] : 0.0,
                'product_external_id' => isset($item['product_id']) && $item['product_id'] !== null
                    ? 'product-' . (int) $item['product_id']
                    : null,
                'iccid_code' => $item['iccid_code'] ?? null,
            ];
        }

        return [
            'external_id' => 'sale-' . (int) ($sale['id'] ?? 0),
            'customer_external_id' => isset($sale['customer_id']) && $sale['customer_id'] !== null
                ? 'customer-' . (int) $sale['customer_id']
                : null,
            'customer_name' => $sale['customer_name'] ?? null,
            'total' => isset($sale['total']) ? (float) $sale['total'] : 0.0,
            'total_paid' => isset($sale['total_paid']) ? (float) $sale['total_paid'] : 0.0,
            'balance_due' => isset($sale['balance_due']) ? (float) $sale['balance_due'] : 0.0,
            'payment_status' => $sale['payment_status'] ?? null,
            'due_date' => $sale['due_date'] ?? null,
            'vat_rate' => isset($sale['vat']) ? (float) $sale['vat'] : null,
            'vat_amount' => isset($sale['vat_amount']) ? (float) $sale['vat_amount'] : null,
            'discount' => isset($sale['discount']) ? (float) $sale['discount'] : null,
            'items' => $normalizedItems,
            'synced_at' => date('c'),
        ];
    }
}
