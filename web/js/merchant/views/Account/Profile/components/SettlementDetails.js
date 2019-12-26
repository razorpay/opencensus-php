import { Component } from 'react';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';
import { fetchGST } from 'merchant/reducers/profile';
import { openModal } from 'merchant_common/reducers/modals';
import AddGST from 'merchant/views/Account/Profile/components/AddGST';
import ShowWhen from 'merchant/components/ShowWhen';
import Popover, { PopoverBody } from 'common/ui/Popover';
import Group, { GroupItem } from 'common/ui/Group';
import Amount from 'common/ui/Amount';

@connect(
  state => ({
    ...state.profile,
    user: state.session.user,
    current_balance: state.home.current_balance,
  }),
  {
    openModal,
  }
)
export default class SettlementDetails extends Component {
  openAddGSTModal = () => {
    this.props.openModal({
      size: 'small',
      component: <AddGST />,
    });
  };

  viewSettlementSchedule = () => {
    console.log('Hi');
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
