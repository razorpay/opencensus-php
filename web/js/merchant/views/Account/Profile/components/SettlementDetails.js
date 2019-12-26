import { Component } from 'react';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';
import { fetchGST } from 'merchant/reducers/profile';
import { openModal } from 'merchant_common/reducers/modals';
import AddGST from 'merchant/views/Account/Profile/components/AddGST';
import ShowWhen from 'merchant/components/ShowWhen';
import Popover, { PopoverBody } from 'common/ui/Popover';
import Group, { GroupItem } from 'common/ui/Group';

@connect(state => ({ ...state.profile, user: state.session.user }), {
  fetchGST,
  openModal,
})
export default class SettlementDetails extends Component {
  componentWillMount() {
    this.props.fetchGST();
  }

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
    let { merchant_gst, rzp_gst, user } = this.props;
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
            <span>₹7,12,618.36 </span>
          </div>

          <div class="list-group-item">
            <div>
              Next Settlement
              <i class="i i-info-circle" />
              <Popover theme="dark">
                <PopoverBody>
                  This is a tentative amount, it might vary by refund and
                  others.
                </PopoverBody>
              </Popover>
            </div>
            <div
              style={{
                display: 'flex',
                width: '40%',
                justifyContent: 'space-evenly',
              }}
            >
              <div>₹7,12,618.36</div>
              <div>01 Dec, 5PM</div>
              <div class="btn-link">Know More</div>
            </div>
          </div>
        </div>
      </div>
    );
  }
}
