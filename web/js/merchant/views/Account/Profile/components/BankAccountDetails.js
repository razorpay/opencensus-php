import React, { useEffect, useRef } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';
import DetailRow from 'merchant/components/DetailRow';
import Popover, { PopoverBody } from 'common/ui/Popover';

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

  return (
    <div class="panel panel-default" ref={bankAccountSectionRef}>
      <div class="panel-heading">
        Bank Account
        {settlement_amount.no_settlement && settlement_amount.no_settlement.on_hold && (
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
        {((settlement_amount.no_settlement && !settlement_amount.no_settlement.on_hold) ||
          !settlement_amount.no_settlement) &&
          isBankAccountChangeAllowed !== null &&
          !user.blockBankAccountUpdate() &&
          (isBankAccountChangeAllowed ? (
            <a class="pull-right" onClick={onChangeBankAccountDetails}>
              Request Change
            </a>
          ) : (
            <span class="pull-right" style={{ opacity: '0.5' }}>
              Request Under Review
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
