import React from 'react';
import Amount from 'common/ui/Amount';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { getFormattedAmountNew } from 'common/utils/rzp-utils';

function computeRepaymentSchedule(repaymentDate, diffDays) {
  const repaymentStartDate = moment(repaymentDate).subtract(diffDays, 'days');
  const repaymentEndDate = moment(repaymentDate);

  const startMonth = repaymentStartDate.format('MMM');
  const startYear = repaymentStartDate.year();

  const endMonth = repaymentEndDate.format('MMM');
  const endYear = repaymentEndDate.year();

  let repaymentSchedule = '';

  if (startMonth === endMonth) {
    repaymentSchedule = `${startMonth} ${repaymentStartDate.date()} - ${repaymentEndDate.date()}`;
  } else if (startYear !== endYear) {
    repaymentSchedule = `${startMonth} ${repaymentStartDate.date()} ${startYear} - ${endMonth} ${repaymentEndDate.date()} ${endYear}`;
  } else if (startMonth !== endMonth) {
    repaymentSchedule = `${startMonth} ${repaymentStartDate.date()} - ${endMonth} ${repaymentEndDate.date()}`;
  }

  return repaymentSchedule;
}

function WithdrawnAmountSummary({ principle, interest, repaymentDate, roi, diffDays }) {
  const totalRepayableAmount = parseFloat(principle * 100).toFixed(2);
  const interestPopoverContent = `(${getFormattedAmountNew(
    principle * 100,
    true,
  )} X ${roi}%) * ${diffDays} ${diffDays > 1 ? 'days' : 'day'}`;
  const repaymentSchedule = computeRepaymentSchedule(repaymentDate, diffDays);

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
            <Amount value={totalRepayableAmount} parentQuerySelector=".withdrawals__top-summary" />
          </div>
        </div>
        <div className="withdrawals__credit-meta__list-item">
          <div className="description__wrapper bordered-bottom no-top-padding">
            <div className="description flex">
              <p>Interest</p>
              <small className="help-content" style={{ paddingLeft: '4px' }}>
                <i
                  className="i i-info-outline"
                  // onMouseOver={() => trackMouseOver('tenure')}
                />
                <Popover align="top" theme="dark" parentQuerySelector=".withdrawals__top-summary">
                  <PopoverBody>
                    <div class="text-center">{interestPopoverContent}</div>
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
      <div className="withdrawals__footer left-border">
        This amount will be deducted in {diffDays} instalments from your settlement balance between{' '}
        <strong>{repaymentSchedule}</strong> on a daily basis
      </div>
    </div>
  );
}

export default WithdrawnAmountSummary;
