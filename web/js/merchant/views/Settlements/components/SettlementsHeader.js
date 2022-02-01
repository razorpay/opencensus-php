import React from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import TestModeBanner from 'merchant/components/TestModeBanner';
import { openModal as fnOpenModal, closeModal } from 'merchant_common/reducers/modals';
import SettlementScheduleV2 from 'merchant/views/Settlements/components/SettlementScheduleV2';
import { handleAnalytics } from '../Settlements/analytics';
import SettlementsBanner from './SettlementsBanner';
import BalanceDetails from './BalanceDetails';
import SettleNow from './SettleNow';

function SettlementsHeader(props) {
  const {
    user,
    settlement_amount,
    settlementConfig,
    openModal,
    settlementExists,
    checkIfFirstEverSettlement,
    esOndemandSettlementEnabled,
  } = props;

  const { no_settlement } = settlement_amount.data;

  const isOnTemporaryHold = settlementConfig?.data?.config?.features?.hold?.status;
  const isOnHold = no_settlement?.on_hold;
  const isSettlementOnHold = isOnTemporaryHold || isOnHold;

  const viewSettlementCycle = () => {
    openModal({
      size: 'medium',
      component: <SettlementScheduleV2 />,
    });

    window.rzpAnalytics({
      eventCategory: 'Settlement Revamp',
      eventAction: 'View Settlement Cycle',
      eventLabel: `Settlements`,
    });
    handleAnalytics('settlement cycle', 'clicked');
  };

  return (
    <div className="settlements-header">
      <div>
        <TestModeBanner />
      </div>
      <div className="content-wrapper no-padding">
        <div className="content">
          <div className="left-content">
            <div>
              {user.isOrgAllowedFunctionality('current_balance') && (
                <div className="flex settlement-balance-amount">
                  <BalanceDetails />
                  {!isSettlementOnHold &&
                    user.isOndemandSettlementEnabled &&
                    user.isAllowedView('early_settlement') && (
                      <SettleNow
                        settlementExists={settlementExists}
                        esOndemandSettlementEnabled={esOndemandSettlementEnabled}
                        checkIfFirstEverSettlement={checkIfFirstEverSettlement}
                      />
                    )}
                  <br />
                </div>
              )}
            </div>
          </div>
          <div className="right-content">
            <span>
              <a
                className="btn btn-link"
                href="http://razorpay.com/settlement"
                target="_blank"
                rel="noopener noreferrer"
              >
                How settlements work? <i className="i i-external-link link-icon" />
              </a>
            </span>
            <span className="border-left">
              <span className="btn btn-link" onClick={viewSettlementCycle}>
                <i className="i i-clock clock-icon" /> View Settlement Cycle
              </span>
            </span>
          </div>
        </div>
        <SettlementsBanner />
      </div>
    </div>
  );
}

const mapStateToProps = (state) => {
  return {
    user: state.session.user,
    mode: state.session.mode,
    settlement_amount: state.home.settlement_amount,
    holidayList: state.settlement.holidayList,
    ...state.home,
    settlementConfig: state.settlement.config,
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators({ openModal: fnOpenModal, closeModal }, dispatch);
};

export default connect(mapStateToProps, mapDispatchToProps)(SettlementsHeader);
