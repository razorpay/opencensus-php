import { useState } from 'react';
import { connect } from 'react-redux';
import { showNotification } from 'merchant_common/reducers/notifications';
import { getCustomURL } from 'merchant/components/DocsLink';
import TextHighlighter from 'common/ui/TextHighlighter';
import { bindActionCreators } from 'redux';
import { merchantFetch } from 'merchant/utils/ajax';

function FeeBearerSelfserver(props) {
  const [feeBearer, setfeeBearer] = useState(props.user.merchant.fee_bearer);

  const handleToggle = async (type) => {
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
      }
    } catch ({ errors }) {
      props.showNotification({
        type: 'error',
        message: errors,
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
              <p>We charge you the fee for the payment and settle the remaining amount to you.</p>
              <br />
            </div>
          </div>
          <div class="col-sm-6 p5">
            <div class={`fee-bearer-panel-col ${feeBearer === 'customer' ? 'active' : null}`}>
              <h4>
                <b>Customer pays the fee</b>
                <input
                  type="radio"
                  class="radio-pointer"
                  checked={feeBearer === 'customer'}
                  onChange={() => handleToggle('customer')}
                />
              </h4>
              <p>
                We charge the cutomer the fees over the price of product and settle the amount of
                the product.
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
