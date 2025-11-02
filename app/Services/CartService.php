<?php

namespace App\Services;

use Illuminate\Support\Facades\Session;

class CartService
{
    protected $sessionKey = 'booking_cart';

    /**
     * Get all cart items
     */
    public function getItems(): array
    {
        return Session::get($this->sessionKey, []);
    }

    /**
     * Get cart item count
     */
    public function getCount(): int
    {
        return count($this->getItems());
    }

    /**
     * Get total price
     */
    public function getTotal(): float
    {
        $items = $this->getItems();
        $total = 0;

        foreach ($items as $item) {
            $total += ($item['seatPrice'] * $item['quantity']);
        }

        return $total;
    }

    /**
     * Add item to cart
     */
    public function addItem(array $item): void
    {
        $items = $this->getItems();
        
        // Generate unique key for the item (movie + date + time + cinema)
        $itemKey = $this->generateItemKey($item);
        
        // Check if item already exists, update quantity if so
        $existingIndex = $this->findItemIndex($itemKey);
        
        if ($existingIndex !== null) {
            $items[$existingIndex]['quantity'] += $item['quantity'];
        } else {
            $item['_key'] = $itemKey;
            $items[] = $item;
        }

        Session::put($this->sessionKey, $items);
    }

    /**
     * Remove item from cart by index
     */
    public function removeItem(int $index): void
    {
        $items = $this->getItems();
        
        if (isset($items[$index])) {
            array_splice($items, $index, 1);
            Session::put($this->sessionKey, $items);
        }
    }

    /**
     * Clear all cart items
     */
    public function clear(): void
    {
        Session::forget($this->sessionKey);
    }

    /**
     * Generate unique key for cart item
     */
    protected function generateItemKey(array $item): string
    {
        return sprintf(
            '%s|%s|%s|%s',
            $item['movieId'],
            $item['date'],
            $item['time'],
            $item['cinema']
        );
    }

    /**
     * Find item index by key
     */
    protected function findItemIndex(string $key): ?int
    {
        $items = $this->getItems();
        
        foreach ($items as $index => $item) {
            if (($item['_key'] ?? null) === $key) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Validate cart items (check availability, pricing, etc.)
     */
    public function validate(): array
    {
        $items = $this->getItems();
        $errors = [];

        // Add validation logic here if needed
        // For now, basic validation is in the controller

        return $errors;
    }
}
