import React from 'react';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { TOOLTIP_DESCRIPTIONS } from '../Loans/constants';

function SettlementAccountDetails({ user, showFinancerDetails, trackGAEvents = true, _fromWhere }) {
  const trackMouseOver = () => {
    if (!trackGAEvents) return;

    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - WCL LOS',
      eventAction: 'TOOLTIP | IFSC CODE',
      eventLabel: `${_fromWhere}`,
    });
  };
  return (
    <div className="loan-offer-details-wrapper">
      <div className="section loan-offer-summary-section">
        <div className="loan-offer-summary-wrapper">
          <p className="loan-offer-summary-title">Account Number</p>
          <p className="loan-offer-value">{user.bank_account_number}</p>
        </div>
        <vr />
        <div className="loan-offer-summary-wrapper">
          <p className="loan-offer-summary-title">
            IFSC Code
            <small className="help-content" style={{ paddingLeft: '4px' }}>
              <i className="i i-info-outline" onMouseOver={trackMouseOver} />
              <Popover align="top" theme="dark">
                <PopoverBody>
                  <div class="text-left">{TOOLTIP_DESCRIPTIONS.ifsc_code}</div>
                </PopoverBody>
              </Popover>
            </small>
          </p>
          <p className="loan-offer-value no-padding">{user.bank_branch_ifsc}</p>
        </div>
      </div>
      {showFinancerDetails ? (
        <div className="financer-details">
          {/*<div className="details-wrapper">*/}
          {/*  <p className="title">Financed By</p>*/}
          {/*  <p className="description">{financer}</p>*/}
          {/*</div>*/}
          <small>○ The bank account details where the money will be settled to</small>
        </div>
      ) : (
        <div class="m-l m-b">
          <small>○ The bank account details where the money will be settled to</small>
        </div>
      )}
    </div>
  );
}

export default SettlementAccountDetails;
