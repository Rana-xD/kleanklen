<?php

namespace Botble\Ecommerce\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'cms:truncate-orders', description: 'Truncate all ecommerce order data')]
class TruncateOrdersCommand extends Command
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
            'ec_orders',
        ];

        // Add marketplace tables if they exist
        $marketplaceTables = [
            'mp_customer_revenues',
            'mp_customer_withdrawals',
        ];

        Schema::disableForeignKeyConstraints();

        $truncatedCount = 0;
        foreach (array_merge($tables, $marketplaceTables) as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
                $truncatedCount++;
                $this->components->info("Truncated: {$table}");
            }
        }

        Schema::enableForeignKeyConstraints();

        $this->components->success("Successfully truncated {$truncatedCount} tables.");

        return self::SUCCESS;
    }

    protected function configure(): void
    {
        parent::configure();

        $this->addOption('force', null, InputOption::VALUE_NONE, 'Skip confirmation prompt');
    }
}
