import React, { useEffect } from 'react';
import { connect } from 'react-redux';
import Features from 'merchant/components/OnBoarding/Slides/Features';
import { sendToLumberjack } from 'common/utils/analytics';

const objectName = 'super_checkout_landing_page2';
const screen = 'SuperCheckoutOnboarding';

const MagicCheckoutFeatures = (props) => {
  useEffect(() => {
    sendToLumberjack({
      eventName: `${objectName}_loaded`,
      properties: {
        screen,
        status: props.magic_checkout_status,
        merchant_id: props.user.current,
      },
    });
  }, []);

  const onClickBack = (callback) => {
    sendToLumberjack({
      eventName: `${objectName}_back_clicked`,
      properties: {
        screen,
        status: props.magic_checkout_status,
        merchant_id: props.user.current,
      },
    });
    props.prev();
    callback();
  };

  return <Features {...props} prev={onClickBack} />;
};

const mapStateToProps = (state) => ({
  magic_checkout_status: state.magicCheckout.status,
  user: state.session.user,
});

export default connect(mapStateToProps, null)(MagicCheckoutFeatures);
