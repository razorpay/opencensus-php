import React from 'react';
import Amount from 'common/ui/Amount';

const Loader = () => (
  <div class="flex">
    <p className="PlaceholderLoader" />
    <p className="PlaceholderLoader" />
  </div>
);

export default function CreditSummary({
  internalCreditBalance,
  minWithdrawableAmount,
  maxWithdrawableAmount,
  withdrawalConfiguration,
  loading,
}) {
  return (
    <div className="withdrawals__credit-meta">
      <div className="title__wrapper">
        <img
          src={`/dist/css/assets/capital/credit_details.svg`}
          alt="Loading icon"
        />
        <p className="title">Your Credit Details</p>
        {internalCreditBalance < minWithdrawableAmount && (
          <button
            className="btn btn-outline btn-danger pull-right low-balance-badge"
            disabled
          >
            <strong>LOW BALANCE</strong>
          </button>
        )}
      </div>
      <div className="withdrawals__credit-meta__list">
        <div className="withdrawals__credit-meta__list-item">
          <img
            src={`/dist/css/assets/capital/available_balance.svg`}
            alt="Loading icon"
          />
          <div className="description__wrapper bordered-bottom">
            <div className="description">
              <strong>Available Withdrawable Balance</strong>
              <p className="text-fade text-small">as a credit limit</p>
            </div>
            {loading ? (
              <Loader />
            ) : (
              <div class="flex">
                {internalCreditBalance < minWithdrawableAmount && (
                  <i className="i i-info-circle text-danger credit-balance-indicator" />
                )}
                <Amount value={internalCreditBalance} className="pull-right" />
                {internalCreditBalance > maxWithdrawableAmount && (
                  <i class="i i-arrow-up text-success credit-balance-indicator pull-right" />
                )}
              </div>
            )}
          </div>
        </div>
        <div className="withdrawals__credit-meta__list-item">
          <img
            src={`/dist/css/assets/capital/withdrawable_amount.svg`}
            alt="Loading icon"
          />
          <div className="description__wrapper bordered-bottom">
            <div className="description">
              <strong>Maximum Withdrawable Amount</strong>
              <p className="text-small text-fade">at a single time</p>
            </div>
            {loading ? (
              <Loader />
            ) : (
              <Amount value={maxWithdrawableAmount} className="pull-right" />
            )}
          </div>
        </div>
        <div className="withdrawals__credit-meta__list-item">
          <img
            src={`/dist/css/assets/capital/due_amount.svg`}
            alt="Loading icon"
          />
          <div className="description__wrapper">
            <div className="description">
              <strong>Due Repayments</strong>
              <p className="text-small text-fade">
                Amount that need to be repayed
              </p>
            </div>
            {loading ? (
              <Loader />
            ) : (
              <div className="flex">
                <Amount
                  value={
                    withdrawalConfiguration.principal_outstanding_balance || 0
                  }
                  className="pull-right"
                />
                {internalCreditBalance === 0 && (
                  <i className="i i-arrow-down text-success credit-balance-indicator pull-right" />
                )}
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
