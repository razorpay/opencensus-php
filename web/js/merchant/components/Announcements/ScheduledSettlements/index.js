import React, { Component } from 'react';
import Button from 'common/new-ui/Button';
import ScheduledModal from 'merchant/views/Settlements/Settlements/components/Modals/ScheduledModal';
import { connect } from 'react-redux';
import * as ModalActions from 'merchant_common/reducers/modals';
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import { EVENT_CATEGORY_DASHBOARD_HOME } from 'merchant/containers/Home/ga';

// eslint-disable-next-line no-unused-vars
@connect((state) => ({}), {
  ...ModalActions,
})
export default class EarlyScheduledAnnouncement extends Component {
  render() {
    return (
      <AnnouncementBanner
        class="es-auto-banner"
        theme="primary"
        title="Introducing Early Settlements"
        canBeClosed={true}
        card_id="introducing-early-settlements-banner"
      >
        <span class="es-schedule-banner-text">
          Get your settlements on the same day automtically!{' '}
          <a href="http://razorpay.com/settlement" target="_blank" rel="noreferrer noopener">
            Learn More
          </a>
        </span>
        <Button.Secondary
          class="pull-right"
          onClick={() => {
            this.props.openModal({
              component: (
                <ScheduledModal
                  eventCategory={EVENT_CATEGORY_DASHBOARD_HOME}
                  fromWhere="Home Announcement Banner"
                />
              ),
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
