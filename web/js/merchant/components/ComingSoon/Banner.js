import { useCallback } from 'react';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import Button from 'common/new-ui/Button';
import useLocalStorageCheck from 'merchant/hooks/localStorageCheck';

import ComingSoonSuccess from "assets/coming_soon/success.svg"

const Banner = (props) => {
  const [isHidden, toggleIsHidden] = useLocalStorageCheck(
    `${props.product.replace(' ', '-')}-${props.mode}-${props.merchant_id}`,
  );

  const interestClicked = useCallback(() => {
    const key = props.product.toLowerCase().split(' ').join('_');
    analyticsTrack({
      objectName: `${props.product} Coming Soon Screen`,
      actionName: 'clicked',
      screen: 'Coming Soon',
      properties: {
        location: props.product,
        interested_product: key,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });

    window.rzpQ.productOnboarding().interaction(`coming_soon`, {
      product: key,
    });

    props.interestClicked && props.interestClicked();

    toggleIsHidden();
  }, [toggleIsHidden]);

  return (
    <div className="ComingSoon--Banner">
      {isHidden ? (
        <div className="request-success">
          <img src={ComingSoonSuccess} />
          <div>
            <strong>We're glad you're interested in Razorpay {props.product}!</strong>
            <p>We’ll share details of early access on your registered email ID in a few days.</p>
          </div>
        </div>
      ) : (
        <div className="request">
          <strong>Want early access to {props.product}?</strong>
          <Button.Primary onClick={interestClicked}>
            Yes, I am Interested
            <span className="m-l">
              <i className="i i-arrow-forward"></i>
            </span>
          </Button.Primary>
        </div>
      )}
    </div>
  );
};

Banner.propTypes = {
  product: PropTypes.string,
};

export default connect((state) => ({
  merchant_id: state.session.user.current,
  mode: state.session.mode,
}))(Banner);
