import React, { useEffect } from 'react';
import { connect } from 'react-redux';
import Landing from 'merchant/components/OnBoarding/Slides/Landing';
import { RZPFeatures } from 'merchant/helpers/data';
import { LANDING_CONTENT } from 'merchant/views/SuperCheckout/data';
import DescriptionLink from 'merchant/views/SuperCheckout/components/DescriptionLink';
import {
  loadWaitlistForm,
  loadFeedbackForm,
} from 'merchant/views/SuperCheckout/utils/waitlistForm';
import { updateSuperCheckoutStatus } from 'merchant/reducers/superCheckout';
import { sendToLumberjack } from 'common/utils/analytics';

const objectName = 'SuperCheckoutLandingPage1';
const screen = 'SuperCheckoutOnboarding';

const SuperCheckoutLanding = (props) => {
  useEffect(() => {
    sendToLumberjack({
      eventName: `${objectName}Loaded`,
      properties: {
        screen,
        status: props.superCheckout.status,
      },
    });
  }, []);

  const onReadMoreClicked = (callback) => {
    sendToLumberjack({
      eventName: `ReadMoreClicked`,
      properties: {
        screen,
        status: props.superCheckout.status,
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
                eventName: 'WaitlistFormOpened',
                properties: {
                  screen,
                  status: props.superCheckout.status,
                },
              });
              loadWaitlistForm(props.user, () => {
                sendToLumberjack({
                  eventName: 'WaitlistFormFilled',
                  properties: {
                    screen,
                    status: props.superCheckout.status,
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
          We are sorry to see you opt out of using Razorpay’s Super Checkout experience. Please tell
          us what went wrong by sharing your feedback with us via
          <DescriptionLink
            onClick={(e) => {
              e.preventDefault();
              sendToLumberjack({
                eventName: 'FeedbackFormOpened',
                properties: {
                  screen,
                  status: props.superCheckout.status,
                },
              });
              loadFeedbackForm(props.user);
            }}
          >
            this
          </DescriptionLink>
          form. You can Re-activate Super Checkout at your end. For any queries, please raise a
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
      title="Super Checkout"
      feature={RZPFeatures.SUPER_CHECKOUT}
      ytVideoUrl="https://www.youtube-nocookie.com/embed/ItrlJ6WgfKg"
      heading={getHeadingComponent(LANDING_CONTENT[props.superCheckout.status])}
      desc={descriptionContainer(props.superCheckout.status)}
    />
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
  superCheckout: state.superCheckout,
});

const mapDispatchToProps = (dispatch) => ({
  updateStatus: (payload) => dispatch(updateSuperCheckoutStatus(payload)),
});

export default connect(mapStateToProps, mapDispatchToProps)(SuperCheckoutLanding);
