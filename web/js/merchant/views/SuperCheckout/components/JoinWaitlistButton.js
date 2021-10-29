import React from 'react';
import { connect } from 'react-redux';
import { updateSuperCheckoutStatus } from 'merchant/reducers/superCheckout';
import { CTA_TEXT } from 'merchant/views/SuperCheckout/data';
import { loadWaitlistForm } from 'merchant/views/SuperCheckout/utils/waitlistForm';
import { sendToLumberjack } from 'common/utils/analytics';

const objectName = 'JoinWaitlistCta';
const screen = 'SuperCheckoutOnboarding';

const JoinWaitlistButton = ({ user, superCheckout, updateStatus, children }) => {
  const onClickJoinWaitlist = () => {
    const { current } = user;

    sendToLumberjack({
      eventName: `${objectName}Clicked`,
      properties: {
        screen,
        status: superCheckout.status,
      },
    });
    if (superCheckout.status === 'available') {
      updateStatus({
        merchant_id: current,
        status: 'interested',
      });
    } else if (superCheckout.status === 'interested') {
      sendToLumberjack({
        eventName: `WaitlistFormLoaded`,
        properties: {
          screen,
          status: superCheckout.status,
        },
      });
      loadWaitlistForm(user, () => {
        sendToLumberjack({
          eventName: `WaitlistFormFilled`,
          properties: {
            screen,
            status: superCheckout.status,
          },
        });
        updateStatus({
          merchant_id: current,
          status: 'waitlisted',
        });
      });
    }
  };

  if (['deactivated', 'live', 'waitlisted'].includes(superCheckout?.status)) {
    return null;
  }
  return (
    <>{children(onClickJoinWaitlist, CTA_TEXT[superCheckout.status], superCheckout.loading)}</>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
  superCheckout: state.superCheckout,
});

const mapDispatchToProps = (dispatch) => ({
  updateStatus: (payload) => dispatch(updateSuperCheckoutStatus(payload)),
});

export default connect(mapStateToProps, mapDispatchToProps)(JoinWaitlistButton);
