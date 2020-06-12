import React from 'react';
import Popover, { PopoverBody } from 'common/ui/Popover';
import Amount from 'common/ui/Amount';
import { TOOLTIP_DESCRIPTIONS } from '../constants';

function RepaymentInformation({ amount }) {
  return (
    <div className="loan-repayment-details-wrapper">
      <div className="loan-repayment-details">
        <div className="section loan-amount-details-wrapper">
          <p className="loan-offer-detail-title">
            Daily Repayment Amount
            <small className="help-content" style={{ paddingLeft: '4px' }}>
              <i className="i i-info-outline" />
              <Popover align="top" theme="dark">
                <PopoverBody>
                  <div style={{ textAlign: 'left' }}>
                    {TOOLTIP_DESCRIPTIONS['daily_repayable_amount']}
                  </div>
                </PopoverBody>
              </Popover>
            </small>
          </p>
          <p className="loan-offer-value">
            <Amount value={amount} />
          </p>
        </div>
        <p className="loan-repayment-detail-info">
          Will be collected from your transaction once the loan is disbursed
        </p>
      </div>
    </div>
  );
}

export default RepaymentInformation;
