import React, { useEffect } from 'react';
import { connect } from 'react-redux';
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

const objectName = 'super_checkout_landing_page1';
const screen = 'SuperCheckoutOnboarding';

const MagicCheckoutLanding = (props) => {
  useEffect(() => {
    if (!props.magicCheckout.loading) {
      sendToLumberjack({
        eventName: `${objectName}_loaded`,
        properties: {
          screen,
          status: props.magicCheckout.status,
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
        status: props.magicCheckout.status,
        merchant_id: props.user.current,
      },
    });
    props.next();
    callback();
  };

  const descriptionContainer = (status) => (
    <>
      {status === 'available' && (
        <div>
          Reduce your cart abandonment rates & Return to Origin (RTO) by offering a better & smarter
          shopping experience to your customers. Get protection against all COD RTOs, reduce RTOs
          and improve order conversion rates!
        </div>
      )}
      {status === 'interested' && (
        <div>
          Thank you for your interest. Please fill
          <DescriptionLink
            onClick={(e) => {
              e.preventDefault();
              sendToLumberjack({
                eventName: 'super_checkout_waitlist_form_loaded',
                properties: {
                  screen,
                  status: props.magicCheckout.status,
                  merchant_id: props.user.current,
                },
              });
              loadWaitlistForm(props.user, () => {
                sendToLumberjack({
                  eventName: 'super_checkout_waitlist_form_filled',
                  properties: {
                    screen,
                    status: props.magicCheckout.status,
                    merchant_id: props.user.current,
                  },
                });
                props.updateStatus({
                  merchant_id: props.user.current,
                  status: 'waitlisted',
                });
              });
            }}
          >
            this
          </DescriptionLink>
          small form and we will reach out to you.
        </div>
      )}
      {status === 'waitlisted' && (
        <div>
          Thank you for your interest and filling out the form. We will reach out to you soon!
        </div>
      )}
      {status === 'live' && (
        <div>
          For order details, check your email {props.user.email}. You can also view your details{' '}
          <DescriptionLink href="/app/payments">here</DescriptionLink> in the transactions tab. For
          any queries, please raise a support ticket.
        </div>
      )}
      {status === 'deactivated' && (
        <div>
          We are sorry to see you opt out of using Razorpay’s Magic Checkout experience. Please tell
          us what went wrong by sharing your feedback with us via
          <DescriptionLink
            onClick={(e) => {
              e.preventDefault();
              sendToLumberjack({
                eventName: 'super_checkout_feedback_form_opened',
                properties: {
                  screen,
                  status: props.magicCheckout.status,
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
      )}
    </>
  );

  const getHeadingComponent = ({ heading, icon_classes }) => (
    <div>
      {icon_classes && <i class={icon_classes} />}
      <span>{heading}</span>
    </div>
  );

  return (
    <Landing
      {...props}
      next={onReadMoreClicked}
      title="Magic Checkout"
      feature={RZPFeatures.MAGIC_CHECKOUT}
      ytVideoUrl="https://www.youtube-nocookie.com/embed/ItrlJ6WgfKg"
      heading={getHeadingComponent(LANDING_CONTENT[props.magicCheckout.status])}
      desc={descriptionContainer(props.magicCheckout.status)}
    />
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
  magicCheckout: state.magicCheckout,
});

const mapDispatchToProps = (dispatch) => ({
  updateStatus: (payload) => dispatch(updateMagicCheckoutStatus(payload)),
});

export default connect(mapStateToProps, mapDispatchToProps)(MagicCheckoutLanding);
