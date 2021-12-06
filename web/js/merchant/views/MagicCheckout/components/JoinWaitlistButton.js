import React from 'react';
import { connect } from 'react-redux';
import { updateMagicCheckoutStatus } from 'merchant/reducers/magicCheckout';
import { CTA_TEXT } from 'merchant/views/MagicCheckout/data';
import { loadWaitlistForm } from 'merchant/views/MagicCheckout/utils/waitlistForm';
import { sendToLumberjack } from 'common/utils/analytics';

const objectName = 'super_checkout_join_waitlist_cta';
const screen = 'SuperCheckoutOnboarding';

const JoinWaitlistButton = ({ user, magicCheckout, updateStatus, children }) => {
  const onClickJoinWaitlist = () => {
    const { current } = user;

    sendToLumberjack({
      eventName: `${objectName}_clicked`,
      properties: {
        screen,
        status: magicCheckout.status,
        merchant_id: current,
      },
    });
    if (magicCheckout.status === 'available') {
      updateStatus({
        merchant_id: current,
        status: 'interested',
      });
    } else if (magicCheckout.status === 'interested') {
      sendToLumberjack({
        eventName: `super_checkout_waitlist_form_loaded`,
        properties: {
          screen,
          status: magicCheckout.status,
          merchant_id: current,
        },
      });
      loadWaitlistForm(user, () => {
        sendToLumberjack({
          eventName: `super_checkout_waitlist_form_filled`,
          properties: {
            screen,
            status: magicCheckout.status,
            merchant_id: current,
          },
        });
        updateStatus({
          merchant_id: current,
          status: 'waitlisted',
        });
      });
    }
  };

  if (['deactivated', 'live', 'waitlisted'].includes(magicCheckout?.status)) {
    return null;
  }
  return (
    <>{children(onClickJoinWaitlist, CTA_TEXT[magicCheckout.status], magicCheckout.loading)}</>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
  magicCheckout: state.magicCheckout,
});

const mapDispatchToProps = (dispatch) => ({
  updateStatus: (payload) => dispatch(updateMagicCheckoutStatus(payload)),
});

export default connect(mapStateToProps, mapDispatchToProps)(JoinWaitlistButton);
