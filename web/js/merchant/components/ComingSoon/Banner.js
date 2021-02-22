import { connect } from 'react-redux';
import PropTypes from 'prop-types';
import { useCallback } from 'react';
import analyticsService from '@commander/services/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import DocsLink from 'merchant/components/DocsLink';
import Button from 'common/new-ui/Button';
import useLocalStorageCheck from 'merchant/hooks/localStorageCheck';

const Banner = (props) => {
  const [isHidden, toggleIsHidden] = useLocalStorageCheck(
    `${props.product.replace(' ', '-')}--${props.merchant_id}`,
  );

  const interestClicked = useCallback(() => {
    analyticsService.track({
      objectName: `${props.product} Coming Soon Screen`,
      actionName: 'clicked',
      screen: 'Coming Soon',
      properties: {
        location: props.product,
        interested_product: props.product.toLowerCase().split(' ').join('_'),
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });

    toggleIsHidden();
  }, [toggleIsHidden]);

  return (
    <div class="ComingSoon--Banner">
      {isHidden ? (
        <div class="request-success">
          <img src="/dist/css/assets/coming_soon/success.svg" />
          <div>
            <strong>We're glad you're interested in Razorpay {props.product}!</strong>
            <p>We’ll share details of early access on your registered email ID in a few days.</p>
          </div>
        </div>
      ) : (
        <div class="request">
          <strong>Want early access to {props.product}?</strong>
          <Button.Primary onClick={interestClicked}>
            Yes, I am Interested
            <span class="m-l">
              <i class="i i-arrow-forward"></i>
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
}))(Banner);
