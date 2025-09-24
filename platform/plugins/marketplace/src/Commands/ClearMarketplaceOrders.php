<?php

namespace Botble\Marketplace\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'cms:marketplace:truncate-orders', description: 'Truncate ecommerce order data for marketplace testing')]
class ClearMarketplaceOrders extends Command
{
    public function handle(): int
    {
        if (! $this->option('force') && ! $this->components->confirm('This will permanently delete all order-related data. Continue?')) {
            $this->components->info('Operation cancelled.');

            return self::SUCCESS;
        }

        $tables = [
            'ec_order_return_histories',
            'ec_order_return_items',
            'ec_order_returns',
            'ec_order_tax_information',
            'ec_order_referrals',
            'ec_order_histories',
            'ec_order_product',
            'ec_order_addresses',
            'ec_invoice_items',
            'ec_invoices',
            'ec_shipment_histories',
            'ec_shipments',
            'mp_customer_revenues',
            'mp_customer_withdrawals',
            'ec_orders',
        ];

        Schema::disableForeignKeyConstraints();

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
            }
        }

        Schema::enableForeignKeyConstraints();

        $this->components->info('Marketplace order data truncated successfully.');

        return self::SUCCESS;
    }

    protected function configure(): void
    {
        parent::configure();

        $this->addOption('force', null, InputOption::VALUE_NONE, 'Skip confirmation prompt');
    }
}
