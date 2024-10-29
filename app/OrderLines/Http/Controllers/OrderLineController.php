<?php

namespace App\OrderLines\Http\Controllers;

use App\Http\Controllers\Controller;
use App\OrderLines\Models\OrderLine;
use App\OrderLines\Repositories\OrderLineRepository;
use App\OrderLines\Services\OrderLineService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrderLineController extends Controller
{
    public function __construct(
        private readonly OrderLineService $orderLineService,
        private readonly OrderLineRepository $orderLineRepository
    )
    {}

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $topProducts = $this->orderLineRepository->getTopFiveSellingProducts();

        return view('orders.index', compact('topProducts'));
    }

    /**
     * Update the specified resource in storage.
     * @throws ConnectionException
     */
    public function update(Request $request, OrderLine $orderLine): RedirectResponse
    {
        try {
            $result = $this->orderLineService->updateProductStock($orderLine, 25);
        } catch (ConnectionException $e) {
            return redirect()->back()
                ->with('status', 'error')
                ->with('status_message', 'Failed to update stock: ' . $e->getMessage());
        }

        return redirect()->back()
            ->with('status', $result ? 'success' : 'error')
            ->with('status_message', $result ? 'Stock updated successfully' : 'Failed to update stock');
    }
}
