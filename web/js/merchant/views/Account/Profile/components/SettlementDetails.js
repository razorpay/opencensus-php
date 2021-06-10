import { Component } from 'react';
import { connect } from 'react-redux';
import { openModal } from 'merchant_common/reducers/modals';
import Amount from 'common/ui/Amount';
import SettlementSchedule from 'merchant/views/Settlements/Settlements/components/SettlementSchedule';
import { fetchSchedule, fetchHolidayList } from 'merchant/reducers/settlements/details';
import { fetchCurrentBalance } from 'merchant/reducers/home';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

@connect(
  (state) => ({
    ...state.profile,
    user: state.session.user,
    current_balance: state.home.current_balance,
  }),
  {
    openModal,
    fetchHolidayList,
    fetchSchedule,
    fetchCurrentBalance,
  },
)
export default class SettlementDetails extends Component {
  componentDidMount() {
    this.props.fetchSchedule();
    this.props.fetchHolidayList();
    this.props.fetchCurrentBalance();
  }

  viewSettlementSchedule = () => {
    analyticsTrack({
      objectName: 'view settlement schedule',
      actionName: 'clicked',
      screen: 'my account',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    this.props.openModal({
      size: 'medium',
      component: <SettlementSchedule location="my account" />,
    });

    window.rzpAnalytics({
      eventCategory: 'Settlement Revamp',
      eventAction: 'View Settlement Cycle',
      eventLabel: `My Account`,
    });
  };

  render() {
    let { current_balance } = this.props;

    return (
      <div class="panel panel-default">
        <div class="panel-heading">
          Settlement Details
          <span class="pull-right">
            <a onClick={this.viewSettlementSchedule}>View Settlement Schedule</a>
          </span>
        </div>
        <div class="list-group details-row-container">
          <div class="list-group-item">
            <span>Current Balance</span>
            <span>
              <Amount value={Math.abs(current_balance.data.balance)} currency={'INR'} />
            </span>
          </div>
        </div>
      </div>
    );
  }
}
