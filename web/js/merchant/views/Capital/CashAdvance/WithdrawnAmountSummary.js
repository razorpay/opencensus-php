import React from 'react';
import Amount from 'common/ui/Amount';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { getFormattedAmountNew } from 'common/utils/rzp-utils';

function WithdrawnAmountSummary({
  principle,
  interest,
  repaymentDate,
  roi,
  diffDays,
}) {
  return (
    <div className="withdrawals__credit-meta">
      <div className="title__wrapper">
        <p className="title">Repayment Details</p>
      </div>
      <div className="withdrawals__credit-meta__list">
        <div className="withdrawals__credit-meta__list-item">
          <div className="description__wrapper">
            <div className="description">
              <p>Principle Repayable</p>
            </div>
            <Amount value={principle * 100} className="pull-right" />
          </div>
        </div>
        <div className="withdrawals__credit-meta__list-item">
          <div className="description__wrapper bordered-bottom no-top-padding">
            <div className="description flex">
              <p>Interest Repayable</p>
              <small className="help-content" style={{ paddingLeft: '4px' }}>
                <i
                  className="i i-info-outline"
                  // onMouseOver={() => trackMouseOver('tenure')}
                />
                <Popover align="top" theme="dark">
                  <PopoverBody>
                    <div class="text-center">
                      {`(${getFormattedAmountNew(
                        principle * 100,
                        true
                      )} X ${roi}%) * ${diffDays} ${
                        diffDays > 1 ? 'days' : 'day'
                      }`}
                    </div>
                  </PopoverBody>
                </Popover>
              </small>
            </div>
            <Amount value={parseInt(interest * 100)} className="pull-right" />
          </div>
        </div>
        <div className="withdrawals__credit-meta__list-item">
          <div className="description__wrapper">
            <div className="description">
              <p>Total Repayable Amount</p>
            </div>
            <Amount
              value={parseFloat((interest + principle) * 100).toFixed(2)}
              className="pull-right"
            />
          </div>
        </div>
      </div>
      <div className="block-note text-small m-t">
        Repayment amount will be automatically collected from your settlement
        balance on <strong>{repaymentDate.format('LL')}</strong>.
      </div>
    </div>
  );
}

export default WithdrawnAmountSummary;
