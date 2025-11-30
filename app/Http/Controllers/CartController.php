<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Get cart items
     */
    public function index()
    {
        return response()->json([
            'items' => $this->cartService->getItems(),
            'count' => $this->cartService->getCount(),
            'total' => $this->cartService->getTotal(),
        ]);
    }

    /**
     * Add item to cart
     */
    public function store(Request $request)
    {
        if (!Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Authentication required',
                ], 401);
            }
            return redirect()->route('login');
        }

        try {
            $validated = $request->validate([
                'movieId' => 'required|string',
                'title' => 'required|string',
                'date' => 'required|date|after_or_equal:today',
                'time' => 'required|string',
                'cinema' => 'required|string',
                'quantity' => 'required|integer|min:1|max:20',
                'seatPrice' => 'required|numeric|min:0',
            ]);

            $this->cartService->addItem($validated);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Item added to cart',
                    'items' => $this->cartService->getItems(),
                    'count' => $this->cartService->getCount(),
                    'total' => $this->cartService->getTotal(),
                ]);
            }
            return redirect()->route('booking')->with('message', 'Added to cart');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Failed to add item to cart',
                    'message' => $e->getMessage(),
                ], 400);
            }
            return back()->withErrors(['cart' => 'Failed to add item'])->withInput();
        }
    }

    /**
     * Remove item from cart
     */
    public function destroy($index, Request $request)
    {
        try {
            $this->cartService->removeItem((int) $index);
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Item removed from cart',
                    'items' => $this->cartService->getItems(),
                    'count' => $this->cartService->getCount(),
                    'total' => $this->cartService->getTotal(),
                ]);
            }
            return redirect()->route('booking')->with('message', 'Item removed');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Failed to remove item from cart',
                ], 400);
            }
            return back()->withErrors(['cart' => 'Failed to remove item']);
        }
    }

    /**
     * Clear cart
     */
    public function clear(Request $request)
    {
        $this->cartService->clear();
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Cart cleared',
                'items' => [],
                'count' => 0,
                'total' => 0,
            ]);
        }
        return redirect()->route('booking')->with('message', 'Cart cleared');
    }
}
