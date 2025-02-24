<?php

namespace Botble\Marketplace\Http\Controllers\Fronts;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Ecommerce\Models\Customer;
use Botble\Marketplace\Enums\WithdrawalStatusEnum;
use Botble\Marketplace\Events\WithdrawalRequested;
use Botble\Marketplace\Facades\MarketplaceHelper;
use Botble\Marketplace\Forms\VendorWithdrawalForm;
use Botble\Marketplace\Http\Requests\Fronts\VendorEditWithdrawalRequest;
use Botble\Marketplace\Http\Requests\Fronts\VendorWithdrawalRequest;
use Botble\Marketplace\Models\VendorInfo;
use Botble\Marketplace\Models\Withdrawal;
use Botble\Marketplace\Tables\VendorWithdrawalTable;
use Exception;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Class WithdrawalController
 *
 * This controller handles vendor withdrawal requests in the marketplace.
 * It manages the creation, updating, and viewing of withdrawal requests,
 * including validation of withdrawal amounts and vendor balances.
 */
class WithdrawalController extends BaseController
{
    /**
     * Display a list of vendor withdrawal requests.
     *
     * @param VendorWithdrawalTable $table The withdrawal table instance
     * @return mixed The rendered table view
     */
    public function index(VendorWithdrawalTable $table)
    {
        $this->pageTitle(__('Withdrawals'));

        return $table->renderTable();
    }

    /**
     * Show the form for creating a new withdrawal request.
     * Validates vendor balance and bank information before allowing withdrawal.
     *
     * @return mixed The withdrawal form or error response
     */
    public function create()
    {
        $user = auth('customer')->user();
        $fee = MarketplaceHelper::getSetting('fee_withdrawal', 0);

        if ($user->balance <= $fee || ! $user->bank_info) {
            return $this
                ->httpResponse()
                ->setError()
                ->setNextUrl(route('marketplace.vendor.withdrawals.index'))
                ->setMessage(__('Insufficient balance or no bank information'));
        }

        $this->pageTitle(__('Withdrawal request'));

        return VendorWithdrawalForm::create()->renderForm();
    }

    /**
     * Store a new withdrawal request.
     * Handles the creation of withdrawal records and updates vendor balance.
     *
     * @param VendorWithdrawalRequest $request The validated withdrawal request
     * @return mixed Response indicating success or failure
     */
    public function store(VendorWithdrawalRequest $request)
    {
        $fee = MarketplaceHelper::getSetting('fee_withdrawal', 0);

        /**
         * @var Customer $vendor
         */
        $vendor = auth('customer')->user();
        $vendorInfo = $vendor->vendorInfo;

        if ($request->input('amount') < MarketplaceHelper::getMinimumWithdrawalAmount()) {
            return $this
                ->httpResponse()
                ->setError()
                ->setMessage(__('The minimum withdrawal amount is :amount', [
                    'amount' => format_price(MarketplaceHelper::getMinimumWithdrawalAmount()),
                ]));
        }

        try {
            DB::beginTransaction();

            /**
             * @var Withdrawal $withdrawal
             */
            $withdrawal = Withdrawal::query()->create([
                'fee' => $fee,
                'amount' => $request->input('amount'),
                'customer_id' => $vendor->getKey(),
                'currency' => get_application_currency()->title,
                'bank_info' => $vendorInfo->bank_info,
                'description' => $request->input('description'),
                'current_balance' => $vendorInfo->balance,
                'payment_channel' => $vendorInfo->payout_payment_method,
            ]);

            $vendorInfo->balance -= $request->input('amount') + $fee;

            /**
             * @var VendorInfo $vendorInfo
             */
            $vendorInfo->save();

            event(new WithdrawalRequested($vendor, $withdrawal));

            DB::commit();
        } catch (Throwable | Exception $th) {
            DB::rollBack();

            return $this
                ->httpResponse()
                ->setError()
                ->setMessage($th->getMessage());
        }

        return $this
            ->httpResponse()
            ->setPreviousUrl(route('marketplace.vendor.withdrawals.index'))
            ->setNextUrl(route('marketplace.vendor.withdrawals.show', $withdrawal->getKey()))
            ->withCreatedSuccessMessage();
    }

    /**
     * Show the form for editing a withdrawal request.
     * Only pending withdrawals can be edited.
     *
     * @param int|string $id The withdrawal request ID
     * @return mixed The withdrawal edit form
     */
    public function edit(int|string $id)
    {
        $withdrawal = Withdrawal::query()
            ->where([
                'id' => $id,
                'customer_id' => auth('customer')->id(),
                'status' => WithdrawalStatusEnum::PENDING,
            ])
            ->firstOrFail();

        $this->pageTitle(__('Update withdrawal request #:id', ['id' => $id]));

        return VendorWithdrawalForm::createFromModel($withdrawal)
            ->setUrl(route('marketplace.vendor.withdrawals.edit', $withdrawal->getKey()))
            ->renderForm();
    }

    /**
     * Update a withdrawal request.
     * Allows updating description or canceling the withdrawal.
     *
     * @param int|string $id The withdrawal request ID
     * @param VendorEditWithdrawalRequest $request The validated edit request
     * @return mixed Response indicating success or failure
     */
    public function update(int|string $id, VendorEditWithdrawalRequest $request)
    {
        $withdrawal = Withdrawal::query()
            ->where([
                'id' => $id,
                'customer_id' => auth('customer')->id(),
                'status' => WithdrawalStatusEnum::PENDING,
            ])
            ->firstOrFail();

        $status = WithdrawalStatusEnum::PENDING;
        if ($request->input('cancel')) {
            $status = WithdrawalStatusEnum::CANCELED;
        }

        $withdrawal->fill([
            'status' => $status,
            'description' => $request->input('description'),
        ]);

        $withdrawal->save();

        if ($status === WithdrawalStatusEnum::CANCELED) {
            return $this
                ->httpResponse()
                ->setPreviousUrl(route('marketplace.vendor.withdrawals.index'))
                ->setNextUrl(route('marketplace.vendor.withdrawals.show', $withdrawal->getKey()))
                ->withUpdatedSuccessMessage();
        }

        return $this
            ->httpResponse()
            ->setPreviousUrl(route('marketplace.vendor.withdrawals.index'))
            ->withUpdatedSuccessMessage();
    }

    /**
     * Display a specific withdrawal request.
     *
     * @param int|string $id The withdrawal request ID
     * @return mixed The withdrawal details view
     */
    public function show(int|string $id)
    {
        $withdrawal = Withdrawal::query()
            ->where('id', $id)
            ->where('customer_id', auth('customer')->id())
            ->firstOrFail();

        $this->pageTitle(__('View withdrawal request #:id', ['id' => $id]));

        return VendorWithdrawalForm::createFromModel($withdrawal)
            ->setUrl(route('marketplace.vendor.withdrawals.edit', $withdrawal->getKey()))
            ->renderForm();
    }
}
