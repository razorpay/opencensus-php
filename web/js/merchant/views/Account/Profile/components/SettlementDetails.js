import { Component } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { openModal as fnOpenModal } from 'merchant_common/reducers/modals';
import Amount from 'common/ui/Amount';
import Time from 'common/ui/Time';
import SettlementScheduleV2 from 'merchant/views/Settlements/components/SettlementScheduleV2';
import {
  fetchSchedule as fnFetchSchedule,
  fetchHolidayList as fnFetchHolidayList,
  fetchSettlementConfig as fnFetchSettlementConfig,
} from 'merchant/reducers/settlements/details';
import { fetchCurrentBalance as fnFetchCurrentBalance } from 'merchant/reducers/home';
import { fetchBankAccountChangeStatus as fnFetchBankAccountChangeStatus } from 'merchant/reducers/profile';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import TextHighlighter from 'common/ui/TextHighlighter';
import { SETTELEMENT_CYCLE } from '../deeplink-constants';
import SettlementDetail from 'merchant/views/Settlements/Settlements/components/SettlementDetail';
import Popover, { PopoverBody } from 'common/ui/Popover';

class SettlementDetails extends Component {
  componentDidMount() {
    const {
      user,
      fetchSchedule,
      fetchHolidayList,
      fetchCurrentBalance,
      fetchSettlementConfig,
      fetchBankAccountChangeStatus,
    } = this.props;
    fetchSchedule();
    fetchHolidayList();
    fetchCurrentBalance();
    fetchSettlementConfig();
    fetchBankAccountChangeStatus(user.id);
  }

  viewSettlementSchedule = () => {
    const { openModal } = this.props;
    analyticsTrack({
      objectName: 'view settlement schedule',
      actionName: 'clicked',
      screen: 'my account',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    openModal({
      size: 'medium',
      component: <SettlementScheduleV2 />,
    });

    window.rzpAnalytics({
      eventCategory: 'Settlement Revamp',
      eventAction: 'View Settlement Cycle',
      eventLabel: `My Account`,
    });
  };

  onViewDetailsClick = () => {
    const { user, settlement_amount, openModal } = this.props;
    openModal({
      size: 'medium',
      component: <SettlementDetail user={user} settlementAmount={settlement_amount?.data} />,
    });
  };

  render() {
    const { current_balance, settlement_amount, settlementConfig } = this.props;
    const { no_settlement } = settlement_amount?.data;

    const isOnTemporaryHold = settlementConfig?.data?.config?.features?.hold?.status;

    const isOnHold =
      no_settlement?.on_hold || settlementConfig?.data?.config?.features?.disable?.status;

    const isSettlementOnHold = isOnTemporaryHold || isOnHold;

    const nextSettlement = settlement_amount?.data?.next_settlement_time;

    return (
      <div className="panel panel-default">
        <div className="panel-heading">
          <TextHighlighter hashedWith={SETTELEMENT_CYCLE}>Settlement Details</TextHighlighter>
          <span className="pull-right">
            <span className="nav-link" onClick={this.viewSettlementSchedule}>
              View Settlement Schedule
            </span>
          </span>
        </div>
        <div className="list-group details-row-container">
          <div className="list-group-item">
            <span>Current Balance</span>
            <span>
              <Amount value={Math.abs(current_balance?.data?.balance)} currency="INR" />
            </span>
          </div>
          {isSettlementOnHold && (
            <div className="list-group-item">
              <span>
                <span>Settlement Status</span>
                <small className="help-content">
                  <i className="i i-warning alert-red" />
                  <Popover align="bottom" theme="dark">
                    <PopoverBody>
                      <div>Your settlements are not being processed.</div>
                    </PopoverBody>
                  </Popover>
                </small>
              </span>
              <span>
                <span className="pr-5">
                  {isOnHold
                    ? 'Your funds have been put on hold.'
                    : 'Your funds have been put on temporary hold.'}
                </span>
                <span className="nav-link" onClick={this.onViewDetailsClick}>
                  View Details
                </span>
              </span>
            </div>
          )}
          {nextSettlement && !no_settlement && !isSettlementOnHold && (
            <div className="list-group-item">
              <span>Next Settlement</span>
              <span>
                <Amount value={settlement_amount?.data?.settlement_amount} currency="INR" />
                <span className="divider" />
                <Time className="pr-5" value={nextSettlement} format="DD MMM, hh:mm A" />
                <span className="nav-link" onClick={this.onViewDetailsClick}>
                  Know More
                </span>
              </span>
            </div>
          )}
        </div>
      </div>
    );
  }
}

const mapStateToProps = (state) => {
  return {
    ...state.profile,
    user: state.session.user,
    current_balance: state.home.current_balance,
    settlement_amount: state.home.settlement_amount,
    settlementConfig: state.settlement.config,
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      openModal: fnOpenModal,
      fetchHolidayList: fnFetchHolidayList,
      fetchSchedule: fnFetchSchedule,
      fetchCurrentBalance: fnFetchCurrentBalance,
      fetchSettlementConfig: fnFetchSettlementConfig,
      fetchBankAccountChangeStatus: fnFetchBankAccountChangeStatus,
    },
    dispatch,
  );
};

export default connect(mapStateToProps, mapDispatchToProps)(SettlementDetails);
