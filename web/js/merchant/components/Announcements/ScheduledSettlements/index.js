import React, { Component } from 'react';
import Button from 'component/Button';
import ScheduledModal from 'merchant/containers/Settlements/ScheduledModal';
import { connect } from 'react-redux';
import * as ModalActions from 'merchant_common/reducers/modals';
import Announcement from 'merchant/components/Announcement';

@connect(state => ({}), {
  ...ModalActions,
})
export default class EarlyScheduledAnnouncement extends Component {
  render() {
    return (
      <Announcement
        class="es-auto-banner"
        theme="primary"
        title="Introducing Early Settlements"
        canBeClosed={true}
      >
        <span className="es-schedule-banner-text">
          Get your settlements on the same day automtically!{' '}
          <a href="http://razorpay.com/settlement" target="_blank">
            Learn More
          </a>
        </span>
        <Button.Secondary
          className="pull-right"
          onClick={() => {
            this.props.openModal({
              component: (
                <ScheduledModal fromWhere="Home Announcement Banner" />
              ),
              size: 'small',
              disableClose: true,
            });
          }}
        >
          Enable Now
        </Button.Secondary>
      </Announcement>
    );
  }
}
