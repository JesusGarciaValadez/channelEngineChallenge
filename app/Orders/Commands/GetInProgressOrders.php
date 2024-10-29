<?php

namespace App\Orders\Commands;

use App\Orders\Services\OrderService;
use Illuminate\Console\Command;

class GetInProgressOrders extends Command
{
    protected $signature = 'orders:fetch-in-progress';
    protected $description = 'Fetch all orders with status IN_PROGRESS from ChannelEngine.';

    protected OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        parent::__construct();
        $this->orderService = $orderService;
    }

    public function handle(): void
    {
        try {
            $this->orderService->getOrdersInProgress();
            $this->info('Fetched orders successfully.');
        } catch(\Exception $e) {
            $this->error("Error: {$e->getMessage()}");
        }
    }
}
