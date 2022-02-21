import React, { useEffect } from 'react';
import Popover, { PopoverBody } from 'common/ui/Popover';
import Amount from 'common/ui/Amount';
import { HOTJAR_TRIGGERS, TOOLTIP_DESCRIPTIONS } from '../Loans/constants';
import { triggerHotjarRecording } from 'common/utils/hotjar';
import { isLoanProduct } from '../utils';

function RepaymentInformation({ creditOffer, trackGAEvents = true, _fromWhere, product }) {
  useEffect(() => {
    triggerHotjarRecording(HOTJAR_TRIGGERS.LOAN_REPAYMENT_PAGE);
  }, []);

  const trackMouseOver = () => {
    if (!trackGAEvents) return;

    window.rzpAnalytics({
      eventCategory: 'Dashboard - WCL LOS',
      eventAction: 'TOOLTIP | Repayment Details',
      eventLabel: `${_fromWhere}`,
    });
  };

  return (
    <div className="loan-repayment-details-wrapper">
      <div className="loan-repayment-details">
        <div className="section loan-amount-details-wrapper">
          <p className="loan-offer-detail-title">
            {isLoanProduct(product) ? 'Daily Repayment Amount' : 'Maximum Withdrawable limit'}
            <small className="help-content">
              &nbsp;
              <i className="i i-info-outline" onMouseOver={trackMouseOver} />
              <Popover align="top" theme="dark">
                <PopoverBody>
                  <div class="text-left">{TOOLTIP_DESCRIPTIONS.daily_repayable_amount}</div>
                </PopoverBody>
              </Popover>
            </small>
          </p>
          <p className="loan-offer-value">
            <Amount
              value={
                isLoanProduct(product)
                  ? creditOffer.installment.amount
                  : creditOffer.withdrawal_limit_per_request
              }
            />
          </p>
        </div>
        <p className="loan-repayment-detail-info">
          {isLoanProduct(product)
            ? 'Will be collected from your transaction once the loan is disbursed'
            : 'This is the limit for a single withdrawal.' +
              ' This will increase upon timely repayments'}
        </p>
      </div>
    </div>
  );
}

export default RepaymentInformation;
