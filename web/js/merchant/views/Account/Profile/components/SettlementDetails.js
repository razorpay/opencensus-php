import { Component } from 'react';
import { connect } from 'react-redux';
import { openModal } from 'merchant_common/reducers/modals';
import Amount from 'common/ui/Amount';
import SettlementSchedule from 'merchant/views/Settlements/components/SettlementSchedule';
import {
  fetchSchedule,
  fetchHolidayList,
} from 'merchant/reducers/settlements/details';

@connect(
  state => ({
    ...state.profile,
    user: state.session.user,
    current_balance: state.home.current_balance,
  }),
  {
    openModal,
    fetchHolidayList,
    fetchSchedule,
  }
)
export default class SettlementDetails extends Component {
  componentDidMount() {
    this.props.fetchSchedule();
    this.props.fetchHolidayList();
  }

  viewSettlementSchedule = () => {
    this.props.openModal({
      size: 'medium',
      component: <SettlementSchedule />,
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
            <a onClick={this.viewSettlementSchedule}>
              View Settlement Schedule
            </a>
          </span>
        </div>
        <div class="list-group details-row-container">
          <div class="list-group-item">
            <span>Current Balance</span>
            <span>
              <Amount value={current_balance.data.balance} currency={'INR'} />
            </span>
          </div>
        </div>
      </div>
    );
  }
}
