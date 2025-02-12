import React from 'react';
import Amount from 'common/ui/Amount';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { getFormattedAmountNew } from 'common/utils/rzp-utils';

function WithdrawnAmountSummary({
  showFirstWithdrawalOffer,
  principle,
  interest,
  roi,
  diffDays,
  isInterestTypeReducing = false,
  autoCollection,
}) {
  const totalRepayableAmount = (principle + interest) * 100;
  const interestPopoverContent = `(${getFormattedAmountNew(
    principle * 100,
    true,
  )} X ${roi}%) * ${diffDays} ${diffDays > 1 ? 'days' : 'day'}`;

  const getPopoverContent = () => {
    if (isInterestTypeReducing) {
      return (
        <>
          Earlier you pay before the due date, <br /> lesser the interest amount'
        </>
      );
    }
    return interestPopoverContent;
  };

  return (
    <div className="withdrawals__credit-meta card">
      <div className="title__wrapper">
        <p className="title">Total Repayable Amount</p>
      </div>
      <div className="amount__wrapper" style={{ position: 'relative' }}>
        <Amount value={totalRepayableAmount} parentQuerySelector=".withdrawals__top-summary" />
      </div>
      <div className="withdrawals__credit-meta__list">
        <div className="withdrawals__credit-meta__list-item">
          <div className="description__wrapper">
            <div className="description">
              <p>Principal</p>
            </div>
            <Amount value={principle * 100} parentQuerySelector=".withdrawals__top-summary" />
          </div>
        </div>
        <div className="withdrawals__credit-meta__list-item">
          <div className="description__wrapper bordered-bottom no-top-padding">
            <div className="description flex">
              <p>{isInterestTypeReducing ? 'Reducing Interest' : 'Interest'}</p>
              <small className="help-content" style={{ paddingLeft: '4px' }}>
                <i className="i i-info-outline" />
                <Popover align="top" theme="dark" parentQuerySelector=".withdrawals__top-summary">
                  <PopoverBody>
                    <div className="text-center">{getPopoverContent()}</div>
                  </PopoverBody>
                </Popover>
              </small>
            </div>
            <Amount
              value={parseFloat(interest * 100)}
              className="pull-right"
              parentQuerySelector=".withdrawals__top-summary"
            />
          </div>
        </div>
      </div>
      {showFirstWithdrawalOffer ? (
        <div className="withdrawals__footer-first-withdrawal-info">
          The interest charged will be deposited back into your bank account within a day of
          repayment.
        </div>
      ) : null}
      {autoCollection && (
        <div className="withdrawals__footer left-border">
          This amount will be deducted in {diffDays} installments from your settlement balance
        </div>
      )}
    </div>
  );
}

export default WithdrawnAmountSummary;
