import React, { useEffect } from 'react';
import { connect } from 'react-redux';
import Features from 'merchant/components/OnBoarding/Slides/Features';
import { sendToLumberjack } from 'common/utils/analytics';

const objectName = 'SuperCheckoutLandingPage2';
const screen = 'SuperCheckoutOnboarding';

const SuperCheckoutFeatures = (props) => {
  useEffect(() => {
    sendToLumberjack({
      eventName: `${objectName}Loaded`,
      properties: {
        screen,
        status: props.super_checkout_status,
      },
    });
  }, []);

  const onClickBack = (callback) => {
    sendToLumberjack({
      eventName: `${objectName}BackClick`,
      properties: {
        screen,
        status: props.super_checkout_status,
      },
    });
    props.prev();
    callback();
  };

  return <Features {...props} prev={onClickBack} />;
};

const mapStateToProps = (state) => ({
  super_checkout_status: state.superCheckout.status,
});

export default connect(mapStateToProps, null)(SuperCheckoutFeatures);
