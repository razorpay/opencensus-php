import './style.styl';
import { connect } from 'react-redux';
import React, { useState } from 'react';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import Amount from 'common/ui/Amount';
import moment from 'moment';
import BlockRadio from '../BlockRadioButton/BlockRadio';
import { setMerchantPreferences } from './api';
import {
  VIEWS,
  RZP_MERCHANT_OWNER_TYPE,
  LOC_PRODUCT_TYPE,
  REPAYMENT_PREFERENCES,
} from 'merchant/views/Capital/CashAdvance/constants';
import { setWithdrawalConfig } from 'merchant/reducers/capital/withdrawals';
import ReducingRepaymentTooltip from '../ReducingRepaymentTooltip';
import {
  trackAutomaticDailyDeductionsRadio,
  trackConfirmPreferenceCTA,
  trackEditButton,
  trackRepayManuallyRadio,
} from '../../TrackEvents/trackEvents';

const FirstWithdrawalView = (props) => {
  const {
    withdraw,
    changeView,
    withdrawalAmount,
    repaybleAmount,
    selectedDueDate,
    user,
    isInterestTypeReducing,
  } = props;

  const [paymentPreference, setPaymentPreference] = useState(null);
  const [showWithdraw, setShowWithdraw] = useState(false);
  const [showEdit, setShowEdit] = useState(true);

  const radioHandleClick = (val) => {
    if (val === REPAYMENT_PREFERENCES.AUTOMATIC_DAILY_DEDUCTION) {
      trackAutomaticDailyDeductionsRadio();
    } else {
      trackRepayManuallyRadio();
    }
    setPaymentPreference(val);
  };

  const handleConfirmPreference = () => {
    trackConfirmPreferenceCTA();
    const data = {
      owner_id: user.current,
      owner_type: RZP_MERCHANT_OWNER_TYPE,
      product_type: LOC_PRODUCT_TYPE,
      auto_collection: paymentPreference === REPAYMENT_PREFERENCES.AUTOMATIC_DAILY_DEDUCTION,
    };

    return setMerchantPreferences(data).then((res) => {
      props.setWithdrawalConfig(res);
      setShowWithdraw(true);
    });
  };

  const withdrawBtnHandleClick = async () => {
    setShowEdit(false);
    await withdraw();
  };

  const handleEditClick = () => {
    trackEditButton();
    changeView(VIEWS.WITHDRAW);
  };

  return (
    <div className="withdrawals__action-container card">
      <div className="flex firstview-heading-container">
        <div className="flex gap--8">
          <div className="firstview-heading">
            You are withdrawing{' '}
            <Amount
              className="firstview-heading"
              parentQuerySelector=".withdrawals__top-summary"
              value={withdrawalAmount}
            />
          </div>
          <Button.Transparent disabled={!showEdit} onClick={handleEditClick}>
            Edit
          </Button.Transparent>
        </div>
        <div>
          <span className="repayable-helper-text">repayable </span>
          <strong>
            <Amount
              className="firstview-repay-amount"
              parentQuerySelector=".withdrawals__top-summary"
              value={repaybleAmount}
            />
          </strong>
          <span className="repayable-helper-text"> by </span>
          <strong>{moment(selectedDueDate).format('LL')}</strong>{' '}
          {isInterestTypeReducing ? <ReducingRepaymentTooltip /> : ''}
        </div>
      </div>
      <div className="top-border">
        <div className="flex gap--4 firstview-options-label--container">
          <p className="new-label">New</p>
          <strong>
            <span className="info-label">Set a repayment preference for future withdrawals</span>
          </strong>
        </div>
        <div className="flex firstview-preference--container">
          {Object.keys(REPAYMENT_PREFERENCES).map((item) => (
            <BlockRadio
              key={item}
              value={item}
              disabled={showWithdraw}
              label={
                item === REPAYMENT_PREFERENCES.AUTOMATIC_DAILY_DEDUCTION ? (
                  <div>
                    <p className="firstview-radio--label">
                      <span>Automatic daily deductions </span>
                      <span className="default-label">DEFAULT</span>
                    </p>
                    <p className="firstview-radio--sublabel">from Razorpay Settlement Balance</p>
                  </div>
                ) : (
                  <div>
                    <p className="firstview-radio--label">I will repay manually</p>
                    <p className="firstview-radio--sublabel">from Razorpay Settlement Balance</p>
                  </div>
                )
              }
              checked={paymentPreference === item}
              handleClick={radioHandleClick}
            />
          ))}
          {showWithdraw ? (
            <AsyncBtn.Primary
              onClick={withdrawBtnHandleClick}
              pendingState={<span>Confirm Withdrawal</span>}
            >
              Confirm Withdrawal
            </AsyncBtn.Primary>
          ) : (
            <AsyncBtn.Primary
              disabled={!paymentPreference}
              onClick={handleConfirmPreference}
              pendingState={<span>Confirm Preference</span>}
            >
              Confirm Preference
            </AsyncBtn.Primary>
          )}
        </div>
      </div>

      <p className="firstview-footer--note">
        <strong>Note:</strong> In case you&apos;re unable to repay by the due date, we will attempt
        collection from the payment gateway settlement balance after the due date. This will help
        reduce your liabilities.
      </p>
    </div>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});

const mapDispatchToProps = {
  setWithdrawalConfig,
};

export default connect(mapStateToProps, mapDispatchToProps)(FirstWithdrawalView);
