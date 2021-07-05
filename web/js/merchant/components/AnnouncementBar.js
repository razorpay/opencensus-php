import React, { Component } from 'react';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';
import RazorpayXNitroAnnouncement, {
  nitroCampaignId,
} from 'common/ui/NotificationsDropdown/RazorpayXNitroAnnouncement';
import { closeModal, openModal } from 'merchant_common/reducers/modals';

@connect(
  (state) => ({
    user: state.session.user,
  }),
  {
    openModal,
    closeModal,
  },
)
@RTracking(() => window.rzpQ.component('ScheduledNitroBanner'))
export default class AnnouncementBar extends Component {
  constructor(props) {
    super(props);
  }

  componentDidMount() {
    const tracking = this.props.tracking;
    tracking.trackEvent(
      window.rzpQ.merchantActions().success(`${this.props.fromWhere}_display_promo_notification2`, {
        ...nitroCampaignId(),
      }),
    );
  }

  trackEvents = (fromWhere) => {
    const tracking = this.props.tracking;

    tracking.trackEvent(
      window.rzpQ.merchantActions().initiated(`${fromWhere}_click_promo_notification2_cta1`, {
        ...nitroCampaignId(),
      }),
    );
  };

  render() {
    return (
      <div class="announcement-sidebar">
        <div class="wrapper">
          <h2>Reduce Platform Fee to 1.65% </h2>
          <p>Get a current Account with RazorpayX</p>
        </div>
        <a
          class="Button--secondary Button scheduled-btn-act btn-border"
          target="_blank"
          onClick={(e) => {
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
