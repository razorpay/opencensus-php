import React from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import PopoverComponent, { PopoverBody } from 'common/ui/Popover';
import Amount from 'common/ui/Amount';
import Time from 'common/ui/Time';
import { openModal as fnOpenModal, closeModal } from 'merchant_common/reducers/modals';
import SettlementDetail from 'merchant/views/Settlements/Settlements/components/SettlementDetail';
import CashAdvanceNudge from 'merchant/views/Capital/CashAdvanceNudges';

const BalanceDetails = (props) => {
  const {
    mode,
    user,
    current_balance,
    settlement_amount,
    settlementConfig,
    openModal,
    payments,
  } = props;

  const { no_settlement } = settlement_amount.data;

  const isOnTemporaryHold = settlementConfig?.data?.config?.features?.hold?.status;
  const isOnHold = no_settlement?.on_hold;
  const isSettlementOnHold = isOnTemporaryHold || isOnHold;

  let balance = current_balance.data.balance || 0;
  let currentBalanceClassName = 'amount-current-balance';

  if (balance < 0) {
    balance = Math.abs(balance);
    currentBalanceClassName += ' negative-balance';
  }

  const nextSettlement = settlement_amount?.data?.next_settlement_time;

  const onKnowMoreClick = () => {
    openModal({
      size: 'medium',
      component: <SettlementDetail user={user} settlementAmount={settlement_amount.data} />,
    });

    window.rzpAnalytics?.({
      eventCategory: 'Settlement Revamp',
      eventAction: 'Know more - Next Settlement',
      eventLabel: `Settlements`,
    });
  };

  return (
    <div>
      <div>
        <strong>
          <span className="pr-5">Current Balance:</span>
          <Amount value={balance} currency="INR" className={currentBalanceClassName} />
        </strong>
        <CashAdvanceNudge />
      </div>
      <div>
        {nextSettlement && !no_settlement && !isSettlementOnHold && (
          <span className="font-13">
            <span>&nbsp;</span>
            <strong>
              <Amount
                value={settlement_amount.data.settlement_amount}
                currency="INR"
                className="amount-settlement"
              />
            </strong>
            <span className="pr-5">will be settled on</span>
            <Time
              value={settlement_amount.data.next_settlement_time}
              format="DD MMM YYYY, hh:mm a"
            />
            {settlement_amount.data.reason_for_delay && (
              <span>
                <i className="i i-info-circle info-icon" />
                <PopoverComponent theme="dark" align="bottom">
                  <PopoverBody>
                    <div>{settlement_amount.data.reason_for_delay}</div>
                  </PopoverBody>
                </PopoverComponent>
              </span>
            )}
            <span onClick={onKnowMoreClick} className="btn-link pointer ml-5">
              <b>Know More</b>
            </span>
          </span>
        )}
        {mode === 'live' &&
        no_settlement &&
        !no_settlement.on_hold &&
        payments &&
        payments.items.length > 0 ? (
          <span className="font-13">
            {no_settlement.caption}
            {no_settlement.reason && (
              <span>
                <i className="i i-info-circle info-icon" />
                <PopoverComponent theme="dark" align="bottom">
                  <PopoverBody>
                    <div>{no_settlement.reason}</div>
                  </PopoverBody>
                </PopoverComponent>
              </span>
            )}
          </span>
        ) : null}
      </div>
    </div>
  );
};

const mapStateToProps = (state) => {
  return {
    user: state.session.user,
    mode: state.session.mode,
    settlement_amount: state.home.settlement_amount,
    payments: state.payments,
    ...state.home,
    settlementConfig: state.settlement.config,
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators({ openModal: fnOpenModal, closeModal }, dispatch);
};

export default connect(mapStateToProps, mapDispatchToProps)(BalanceDetails);
