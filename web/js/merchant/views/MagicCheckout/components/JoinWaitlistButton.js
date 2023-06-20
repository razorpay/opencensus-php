import React from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { updateMagicCheckoutStatus } from 'merchant/reducers/magicCheckout';
import { CTA_TEXT } from 'merchant/views/MagicCheckout/data';
import { loadWaitlistForm } from 'merchant/views/MagicCheckout/utils/waitlistForm';
import { sendToLumberjack } from 'common/utils/analytics';
import { MAGIC_CHECKOUT_STATUS } from 'merchant/views/MagicCheckout/constants';

const objectName = 'super_checkout_join_waitlist_cta';
const screen = 'SuperCheckoutOnboarding';

const { LIVE, DEACTIVATED, WAITLISTED, INTERESTED, AVAILABLE } = MAGIC_CHECKOUT_STATUS;

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
    if (magicCheckout.status === AVAILABLE) {
      updateStatus({
        merchant_id: current,
        status: INTERESTED,
      });
    } else if (magicCheckout.status === INTERESTED) {
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
          status: WAITLISTED,
        });
      });
    }
  };

  if ([DEACTIVATED, LIVE, WAITLISTED].includes(magicCheckout?.status) || user.isMagicCheckoutLive) {
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

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      updateStatus: updateMagicCheckoutStatus,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(JoinWaitlistButton);
