import { connect } from 'react-redux';
import React from 'react';

import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import Button from 'common/new-ui/Button';
import { getCustomURL } from 'merchant/components/DocsLink';
import SwitchToPLV2Modal from './components/SwitchToPLV2Modal';

import { openModal, closeModal } from 'merchant_common/reducers/modals';
import track from './track/index';

const bannerText =
  'Your account is pending for migration to the new service. Click on "Switch Now" to switch. To know more, click';
// const cardId = 'Switch to Payment Links V2';

const cta1Text = 'here.';
const cta1Link = getCustomURL('https://razorpay.com/docs/payment-links/api/new/');

const cta2Text = 'Switch Now';

const SwitchToPaymentLinksV2 = React.memo((props) => {
  track.lj.init(props.mode, props.source);

  function openModal() {
    track.lj.banner.switchNow();

    props.openModal({
      size: 'medium',
      component: (
        <SwitchToPLV2Modal
          closeModal={props.closeModal}
          openModal={props.openModal}
          track={track}
        />
      ),
      className: 'PaymentLinks--Migration',
    });
  }

  function trackKnowMoreClick() {
    track.lj.banner.knowMore();
  }

  return (
    <AnnouncementBanner
      title="Important API Changes"
      canBeClosed={false}
      theme="warning"
      bannerKey={`switch-to-payment-links-v2-${props.user.current}`}
      card_id="switch-to-payment-links-v2-banner"
    >
      <span className="display-inline">{bannerText}</span>
      <a
        href={cta1Link}
        target="_blank"
        rel="noreferrer noopener"
        className="btn btn-link"
        style={{ paddingLeft: '2px' }}
        onClick={trackKnowMoreClick}
      >
        {cta1Text}
      </a>{' '}
      <Button.Secondary className="pull-right" onClick={openModal}>
        {cta2Text}
      </Button.Secondary>
    </AnnouncementBanner>
  );
});

export default connect((state) => ({ user: state.session.user, mode: state.session.mode }), {
  openModal,
  closeModal,
})(SwitchToPaymentLinksV2);
