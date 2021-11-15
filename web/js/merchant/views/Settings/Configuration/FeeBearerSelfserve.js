import { useState } from 'react';
import { connect } from 'react-redux';
import { showNotification } from 'merchant_common/reducers/notifications';
import TextHighlighter from 'common/ui/TextHighlighter';
import { bindActionCreators } from 'redux';
import { merchantFetch } from 'merchant/utils/ajax';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import LoaderDots from 'common/ui/LoaderDots';

function FeeBearerSelfserve(props) {
  const [feeBearer, setfeeBearer] = useState(props.user.merchant.fee_bearer);
  const [showLoader, setshowLoader] = useState(false);

  const handleToggle = async (type) => {
    // Track fee bearer toggle
    analyticsTrack({
      objectName: 'Fee bearer',
      actionName: 'Fee bearer toggled',
      screen: 'settings',
      properties: {
        currentFeeBearer: `${props.user.merchant.fee_bearer}`,
        NewFeeBearer: `${type}`,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });

    setshowLoader(true);

    try {
      const response = await merchantFetch({
        url: `merchant/toggle_fee_bearer`,
        method: 'POST',
        data: { fee_bearer: type },
        headers: {
          'Content-Type': 'application/json',
        },
      });

      if (response) {
        setshowLoader(false);
        setfeeBearer(type);
        props.showNotification({
          type: 'success',
          message: `Configuration updated successfully`,
        });
        analyticsTrack({
          objectName: 'Fee bearer',
          actionName: 'Fee bearer result',
          screen: 'settings',
          properties: {
            currentFeeBearer: `${props.user.merchant.fee_bearer}`,
            NewFeeBearer: `${type}`,
            result: 'Success',
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
      }
    } catch ({ errors }) {
      setshowLoader(false);
      props.showNotification({
        type: 'error',
        message: errors,
      });
      analyticsTrack({
        objectName: 'Fee bearer',
        actionName: 'Fee bearer result',
        screen: 'settings',
        properties: {
          currentFeeBearer: `${props.user.merchant.fee_bearer}`,
          NewFeeBearer: `${type}`,
          result: 'Failure',
          failureMessage: `${errors}`,
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      });
    }
  };

  return (
    <div class="panel panel-default fee-bearer-section">
      <div class="panel-heading pl10" style={{ paddingTop: 0 }}>
        <span class="title">
          <TextHighlighter>Fee Bearer</TextHighlighter>{' '}
        </span>

        <p class="subtitle">
          For every payment done on Razorpay, we levy a nominal platform fee. Choose your preferred
          mode of payment from the below options -
        </p>
      </div>

      <div class="panel-body" style={{ paddingBottom: '6px' }}>
        <div class="row">
          <div class="col-sm-6 p5">
            <div class={`fee-bearer-panel-col ${feeBearer === 'platform' ? 'active' : null}`}>
              <h4>
                <b>You pay the fee</b>
                {showLoader && feeBearer === 'customer' ? (
                  <div class="panel-loader">
                    <LoaderDots />
                  </div>
                ) : (
                  <input
                    type="radio"
                    class="radio-pointer"
                    checked={feeBearer === 'platform'}
                    onChange={() => handleToggle('platform')}
                  />
                )}
              </h4>
              <p>Razorpay platform fee would be borne by you. </p>
              <br />
            </div>
          </div>
          <div class="col-sm-6 p5">
            <div class={`fee-bearer-panel-col ${feeBearer === 'customer' ? 'active' : null}`}>
              <h4>
                <b>Convenience fee model</b>
                {showLoader && feeBearer === 'platform' ? (
                  <div class="panel-loader">
                    <LoaderDots />
                  </div>
                ) : (
                  <input
                    type="radio"
                    class="radio-pointer"
                    checked={feeBearer === 'customer'}
                    onChange={() => handleToggle('customer')}
                  />
                )}
              </h4>
              <p>You charge a convenience fee to your customer.</p>
              <br />
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
export default connect(
  (state) => ({
    user: state.session.user,
  }),
  (dispatch) => bindActionCreators({ showNotification }, dispatch),
)(FeeBearerSelfserve);
