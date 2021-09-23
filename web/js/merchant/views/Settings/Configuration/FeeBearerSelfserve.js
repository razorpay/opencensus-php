import { useState } from 'react';
import { connect } from 'react-redux';
import { showNotification } from 'merchant_common/reducers/notifications';
import TextHighlighter from 'common/ui/TextHighlighter';
import { bindActionCreators } from 'redux';
import { merchantFetch } from 'merchant/utils/ajax';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

function FeeBearerSelfserver(props) {
  const [feeBearer, setfeeBearer] = useState(props.user.merchant.fee_bearer);

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
          For every payment done on Razorpay, we charge a small transaction fee. Choose who would
          pay this fee
        </p>
      </div>

      <div class="panel-body" style={{ paddingBottom: '6px' }}>
        <div class="row">
          <div class="col-sm-6 p5">
            <div class={`fee-bearer-panel-col ${feeBearer === 'platform' ? 'active' : null}`}>
              <h4>
                <b>You pay the fee</b>
                <input
                  type="radio"
                  class="radio-pointer"
                  checked={feeBearer === 'platform'}
                  onChange={() => handleToggle('platform')}
                />
              </h4>
              <p>You pay the fee for the use of payment infrastructure.</p>
              <br />
            </div>
          </div>
          <div class="col-sm-6 p5">
            <div class={`fee-bearer-panel-col ${feeBearer === 'customer' ? 'active' : null}`}>
              <h4>
                <b>Convenience Fee Model</b>
                <input
                  type="radio"
                  class="radio-pointer"
                  checked={feeBearer === 'customer'}
                  onChange={() => handleToggle('customer')}
                />
              </h4>
              <p>
                You charge a convenience fee to your customer for the use of technology
                infrastructure.
              </p>
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
)(FeeBearerSelfserver);
