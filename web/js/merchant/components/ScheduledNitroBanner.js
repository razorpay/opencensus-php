import React, { Component } from 'react';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';
import { compose, bindActionCreators } from 'redux';

import RazorpayXNitroAnnouncement, {
  nitroCampaignId,
} from 'common/ui/NotificationsDropdown/RazorpayXNitroAnnouncement';
import {
  closeModal as fnCloseModal,
  openModal as fnOpenModal,
} from 'merchant_common/reducers/modals';

class ScheduledNitroBanner extends Component {
  componentDidMount() {
    const tracking = this.props.tracking;
    tracking.trackEvent(
      window.rzpQ.merchantActions().success(`${this.props.fromWhere}_display_promo_notification1`, {
        ...nitroCampaignId(),
      }),
    );
  }

  trackEvents = (fromWhere) => {
    const tracking = this.props.tracking;

    tracking.trackEvent(
      window.rzpQ.merchantActions().initiated(`${fromWhere}_click_promo_notification1_cta1`, {
        ...nitroCampaignId(),
      }),
    );
  };

  getTitle = () => {
    return 'Reduce platform fee to 1.65% with a RazorpayX Current Account';
  };

  render() {
    return (
      <div class="schedule-enable-container">
        <span class="display-inline">{this.getTitle()}</span>{' '}
        <a
          class="Button--secondary Button scheduled-btn-act btn-border"
          target="_blank"
          rel="noopener noreferrer"
          onClick={() => {
            const { closeModal, openModal } = this.props;

            openModal({
              component: (
                <RazorpayXNitroAnnouncement
                  hideModal={closeModal}
                  fromWhere={this.props.fromWhere}
                />
              ),
              size: 'xlarge',
              className: 'RazorpayXNitroAnnouncement--Modal',
            });
            this.trackEvents(this.props.fromWhere);
          }}
        >
          Learn More
        </a>
      </div>
    );
  }
}

export default compose(
  connect(
    (state) => ({ user: state.session.user }),
    (dispatch) =>
      bindActionCreators({ openModal: fnOpenModal, closeModal: fnCloseModal }, dispatch),
  ),
  // eslint-disable-next-line babel/new-cap
  RTracking(() => window.rzpQ.component('ScheduledNitroBanner')),
)(ScheduledNitroBanner);
