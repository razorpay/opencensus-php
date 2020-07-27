import React from 'react';
import ModalHeader from 'common/ui/ModalHeader';
import Amount from 'common/ui/Amount';
import Button from 'common/new-ui/Button';

function MinWithdrawAmountModal({
  closeModal,
  availableBalance,
  minWithdrawalAmount,
  repayDues,
  trackGA,
}) {
  return (
    <div class="repayment-modal withdrawals">
      <ModalHeader
        class="header"
        title={
          <div class="flex">
            <img
              src={`/dist/css/assets/capital/question_circle.svg`}
              alt="Loading icon"
            />{' '}
            &nbsp;
            <p>Why Can’t I withdraw?</p>
          </div>
        }
        onCloseClick={() => {
          trackGA({
            eventAction: 'Low Balance | Close Icon',
          });
          closeModal();
        }}
      />
      <small class="text-fade description">
        Check the reason for not able to withdraw money.
      </small>
      <div className="overflow-box">
        <div class="repayment-amount-details">
          <p class="no-margin">
            Your current withdrawable balance is lesser than Minimum withdrawal
            amount. You should have higher balance to withdraw more money.
          </p>
        </div>
        <div class="flex p-l p-r m-all">
          <div
            class="panel-body full-width table-bordered no-margin"
            style={{ borderColor: 'rgba(240, 81, 80, 0.82)' }}
          >
            <h3 class="no-margin">
              <Amount value={availableBalance} />
            </h3>
            <span>Withdrawable Balance</span>
            <p class="text-small text-faded">Current</p>
          </div>
          <h3 class="p-all">{' < '}</h3>
          <div className="panel-body full-width table-bordered no-margin">
            <h3 className="no-margin">
              <Amount value={minWithdrawalAmount} />
            </h3>
            <span>Withdrawal Amount</span>
            <p className="text-small text-faded">Minimum</p>
          </div>
        </div>
      </div>
      <div className="summary p-b">
        Please repay your pending due repayments to withdraw more money.
        <div className="m-t m-b flex">
          <Button.Transparent
            className="full-width no-margin"
            onClick={() => {
              trackGA({
                eventAction: "Low Balance | I'll do later",
              });

              closeModal();
            }}
          >
            <strong>Ok, I'll do later</strong>
          </Button.Transparent>
          <button
            className="Button--primary Button m-l full-width no-margin"
            onClick={() => {
              trackGA({
                eventAction: 'Low Balance | Repay Dues',
              });

              closeModal();
              return repayDues();
            }}
          >
            Repay Dues
          </button>
        </div>
      </div>
    </div>
  );
}

export default MinWithdrawAmountModal;
