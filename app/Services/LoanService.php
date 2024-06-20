<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\ReceivedRepayment;
use App\Models\User;
use App\Models\ScheduledRepayment;

class LoanService
{
    /**
     * Create a Loan
     *
     * @param  User  $user
     * @param  int  $amount
     * @param  string  $currencyCode
     * @param  int  $terms
     * @param  string  $processedAt
     *
     * @return Loan
     */
    public function createLoan(User $user, int $amount, string $currencyCode, int $terms, string $processedAt): Loan
    {
        $loan = new Loan([
            'user_id' => $user->id,
            'amount' => $amount,
            'terms' => $terms,
            'outstanding_amount' => $amount,
            'currency_code' => $currencyCode,
            'processed_at' => $processedAt,
            'status' => Loan::STATUS_DUE,
        ]);
        $loan->save();

        $this->createScheduledRepayments($loan);

        return $loan;
    }

    /**
     * Create Scheduled Repayments for a Loan
     *
     * @param  Loan  $loan
     * @return void
     */
    protected function createScheduledRepayments(Loan $loan): void
    {
        $terms = $loan->terms;
        $amountPerTerm = $loan->amount / $terms;

        for ($i = 1; $i <= $terms; $i++) {
            $dueDate = date('Y-m-d', strtotime("+$i months", strtotime($loan->processed_at)));

            $scheduledRepayment = new ScheduledRepayment([
                'loan_id' => $loan->id,
                'amount' => $amountPerTerm,
                'due_date' => $dueDate,
                'status' => ScheduledRepayment::STATUS_DUE,
            ]);
            $scheduledRepayment->save();
        }
    }

    /**
     * Repay Scheduled Repayments for a Loan
     *
     * @param  Loan  $loan
     * @param  int  $amount
     * @param  string  $currencyCode
     * @param  string  $receivedAt
     *
     * @return ReceivedRepayment
     */
    public function repayLoan(Loan $loan, int $amount, string $currencyCode, string $receivedAt): ReceivedRepayment
    {
        $scheduledRepayments = $loan->scheduledRepayments()->where('status', ScheduledRepayment::STATUS_DUE)->get();

        $totalOutstandingAmount = $loan->outstanding_amount;

        if ($amount > $totalOutstandingAmount) {
            $amount = $totalOutstandingAmount;
        }

        foreach ($scheduledRepayments as $scheduledRepayment) {
            if ($amount <= 0) {
                break;
            }

            $repaymentAmount = min($amount, $scheduledRepayment->amount);

            $receivedRepayment = new ReceivedRepayment([
                'loan_id' => $loan->id,
                'scheduled_repayment_id' => $scheduledRepayment->id,
                'amount' => $repaymentAmount,
                'currency_code' => $currencyCode,
                'received_at' => $receivedAt,
            ]);
            $receivedRepayment->save();

            $amount -= $repaymentAmount;
            $scheduledRepayment->amount -= $repaymentAmount;

            if ($scheduledRepayment->amount <= 0) {
                $scheduledRepayment->status = ScheduledRepayment::STATUS_REPAID;
            } else {
                $scheduledRepayment->status = ScheduledRepayment::STATUS_PARTIAL;
            }

            $scheduledRepayment->save();
        }

        $loan->outstanding_amount -= $amount;

        if ($loan->outstanding_amount <= 0) {
            $loan->status = Loan::STATUS_REPAID;
        }

        $loan->save();

        return $receivedRepayment ?? new ReceivedRepayment();
    }
}