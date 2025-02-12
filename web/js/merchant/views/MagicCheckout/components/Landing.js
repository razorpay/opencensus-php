import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import Landing from 'merchant/components/OnBoarding/Slides/Landing';
import { RZPFeatures } from 'merchant/helpers/data';
import { LANDING_CONTENT } from 'merchant/views/MagicCheckout/data';
import DescriptionLink from 'merchant/views/MagicCheckout/components/DescriptionLink';
import {
  loadWaitlistForm,
  loadFeedbackForm,
} from 'merchant/views/MagicCheckout/utils/waitlistForm';
import { updateMagicCheckoutStatus } from 'merchant/reducers/magicCheckout';
import { sendToLumberjack } from 'common/utils/analytics';
import { MAGIC_CHECKOUT_STATUS } from 'merchant/views/MagicCheckout/constants';

const objectName = 'super_checkout_landing_page1';
const screen = 'SuperCheckoutOnboarding';

const MagicCheckoutLanding = (props) => {
  const [status, setStatus] = useState(MAGIC_CHECKOUT_STATUS.AVAILABLE);

  useEffect(() => {
    if (props.user.isMagicCheckoutLive) {
      setStatus(MAGIC_CHECKOUT_STATUS.LIVE);
    } else {
      setStatus(props.magicCheckout.status);
    }
  }, [props.magicCheckout.status, props.user]);

  useEffect(() => {
    if (!props.magicCheckout.loading) {
      sendToLumberjack({
        eventName: `${objectName}_loaded`,
        properties: {
          screen,
          status,
          merchant_id: props.user.current,
        },
      });
    }
  }, [props.magicCheckout.loading]);

  const onReadMoreClicked = (callback) => {
    sendToLumberjack({
      eventName: `super_checkout_read_more_clicked`,
      properties: {
        screen,
        status,
        merchant_id: props.user.current,
      },
    });
    props.next();
    callback();
  };

  function descriptionContainer() {
    switch (status) {
      case MAGIC_CHECKOUT_STATUS.AVAILABLE:
        return (
          <div>
            Reduce your cart abandonment rates & Return to Origin (RTO) by offering a better &
            smarter shopping experience to your customers. Get protection against all COD RTOs,
            reduce RTOs and improve order conversion rates!
          </div>
        );
      case MAGIC_CHECKOUT_STATUS.INTERESTED:
        return (
          <div>
            Thank you for your interest. Please fill
            <DescriptionLink
              onClick={(e) => {
                e.preventDefault();
                sendToLumberjack({
                  eventName: 'super_checkout_waitlist_form_loaded',
                  properties: {
                    screen,
                    status,
                    merchant_id: props.user.current,
                  },
                });
                loadWaitlistForm(props.user, () => {
                  sendToLumberjack({
                    eventName: 'super_checkout_waitlist_form_filled',
                    properties: {
                      screen,
                      status,
                      merchant_id: props.user.current,
                    },
                  });
                  props.updateStatus({
                    merchant_id: props.user.current,
                    status: MAGIC_CHECKOUT_STATUS.WAITLISTED,
                  });
                });
              }}
            >
              this
            </DescriptionLink>
            small form and we will reach out to you.
          </div>
        );
      case MAGIC_CHECKOUT_STATUS.WAITLISTED:
        return (
          <div>
            Thank you for your interest and filling out the form. We will reach out to you soon!
          </div>
        );
      case MAGIC_CHECKOUT_STATUS.LIVE:
        return (
          <div>
            For order details, check your email {props.user.email}. You can also view your details{' '}
            <DescriptionLink href="/app/payments">here</DescriptionLink> in the transactions tab.
            For any queries, please raise a support ticket.
          </div>
        );
      case MAGIC_CHECKOUT_STATUS.DEACTIVATED:
        return (
          <div>
            We are sorry to see you opt out of using Razorpay’s Magic Checkout experience. Please
            tell us what went wrong by sharing your feedback with us via
            <DescriptionLink
              onClick={(e) => {
                e.preventDefault();
                sendToLumberjack({
                  eventName: 'super_checkout_feedback_form_opened',
                  properties: {
                    screen,
                    status,
                    merchant_id: props.user.current,
                  },
                });
                loadFeedbackForm(props.user);
              }}
            >
              this
            </DescriptionLink>
            form. You can Re-activate Magic Checkout at your end. For any queries, please raise a
            support ticket.
          </div>
        );
      default:
        return null;
    }
  }

  const getHeadingComponent = ({ heading, icon_classes }) => (
    <div>
      {icon_classes && <i className={icon_classes} />}
      <span>{heading}</span>
    </div>
  );

  return (
    <Landing
      {...props}
      next={onReadMoreClicked}
      title="Magic Checkout"
      feature={RZPFeatures.MAGIC_CHECKOUT}
      ytVideoUrl="https://www.youtube-nocookie.com/embed/TdZa73eheww"
      heading={getHeadingComponent(LANDING_CONTENT[status])}
      desc={descriptionContainer()}
    />
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

export default connect(mapStateToProps, mapDispatchToProps)(MagicCheckoutLanding);
