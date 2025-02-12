import React, { Component } from 'react';
import Button from 'common/new-ui/Button';
import ScheduledModal from 'merchant/views/Settlements/Settlements/components/Modals/ScheduledModal';
import { connect } from 'react-redux';
import * as ModalActions from 'merchant_common/reducers/modals';
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import { compose } from 'redux';

// eslint-disable-next-line no-unused-vars

class EarlyScheduledAnnouncement extends Component {
  render() {
    return (
      <AnnouncementBanner
        className="es-auto-banner"
        theme="primary"
        title="Introducing Early Settlements"
        canBeClosed={true}
        card_id="introducing-early-settlements-banner"
      >
        <span className="es-schedule-banner-text">
          Get your settlements on the same day automtically!{' '}
          <a href="http://razorpay.com/settlement" rel="noreferrer noopener" target="_blank">
            Learn More
          </a>
        </span>
        <Button.Secondary
          className="pull-right"
          onClick={() => {
            this.props.openModal({
              component: <ScheduledModal />,
              size: 'small',
              disableClose: true,
            });
          }}
        >
          Enable Now
        </Button.Secondary>
      </AnnouncementBanner>
    );
  }
}

export default compose(
  connect(() => ({}), {
    ...ModalActions,
  }),
)(EarlyScheduledAnnouncement);
