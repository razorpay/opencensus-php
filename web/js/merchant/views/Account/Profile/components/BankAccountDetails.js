import React, { useEffect, useRef } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';
import DetailRow from 'merchant/components/DetailRow';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import TextHighlighter from 'common/ui/TextHighlighter';
import { UPDATE_BANK_ACC } from '../deeplink-constants';
const BankAccountDetails = ({
  bankAccount,
  onChangeBankAccountDetails,
  isBankAccountChangeAllowed,
  settlement_amount,
  location,
  user,
}) => {
  const bankAccountSectionRef = useRef(null);

  useEffect(() => {
    if (
      bankAccountSectionRef &&
      bankAccountSectionRef.current &&
      location &&
      location.hash === '#request-bank-account-change'
    ) {
      bankAccountSectionRef.current.scrollIntoView();
    }
  }, [bankAccountSectionRef, location]);

  const isSettlementOnHold =
    (settlement_amount.no_settlement && settlement_amount.no_settlement.on_hold) || false;

  const showRequestChange =
    !isSettlementOnHold &&
    isBankAccountChangeAllowed !== null &&
    !user.blockBankAccountUpdate() &&
    user.activation_status === 'activated' &&
    !user.isOrgAxis;

  return (
    <div class="panel panel-default" ref={bankAccountSectionRef}>
      <div class="panel-heading">
        <TextHighlighter hashedWith={UPDATE_BANK_ACC}>Bank Account</TextHighlighter>
        {settlement_amount.no_settlement &&
          settlement_amount.no_settlement.on_hold &&
          !user.isOrgAxis && (
            <span class="pull-right" style={{ color: 'gray' }}>
              Request Change
              <small class="help-content">
                <i class="i i-info-outline" />
                <Popover align="top" theme="dark">
                  <PopoverBody>
                    <div>The bank account cannot be updated, since your funds are on hold.</div>
                  </PopoverBody>
                </Popover>
              </small>
            </span>
          )}
        {showRequestChange &&
          (isBankAccountChangeAllowed ? (
            <a
              class="pull-right"
              onClick={(...e) => {
                analyticsTrack({
                  objectName: 'bank account edit',
                  actionName: 'clicked',
                  screen: 'my account',
                  properties: {
                    action: 'cancel',
                    ...getCommonAnalyticsProperties(window.rzp_user),
                  },
                });
                onChangeBankAccountDetails(...e);
              }}
            >
              Request Change
            </a>
          ) : (
            <span class="pull-right" style={{ opacity: '0.5' }}>
              Request under review
            </span>
          ))}
      </div>
      <div class="list-group details-row-container">
        <DetailRow label="IFSC Code" value={bankAccount.ifsc} />
        <DetailRow label="Account Number" value={bankAccount.account_number} />
        <DetailRow label="Beneficiary" value={bankAccount.name} />
      </div>
    </div>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});

export default withRouter(connect(mapStateToProps, {})(BankAccountDetails));
