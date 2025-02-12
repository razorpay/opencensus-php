import Button from 'common/new-ui/Button';
import WidgetPreview from '../Preview/Types/WidgetPreview';
import CustomerDetailsPreview from '../Preview/Types/CustomerDetailsPreview';

import { filterSubscriptionPaymentItems } from 'merchant/reducers/subscriptionButtons/create';

import track from '../../track';

export default class ReviewAndCreate extends React.Component {
  render() {
    const { paymentFields } = this.props;

    const oneTimePaymentsFields = filterSubscriptionPaymentItems(paymentFields, true);

    return (
      <div className="Form" style={{ display: this.props.isHidden ? 'none' : '' }}>
        <div className="PaymentButtonForm-ReviewAndCreate Form-content">
          <WidgetPreview {...this.props} />

          {!!oneTimePaymentsFields.length && <WidgetPreview {...this.props} showOneTimePayments />}

          <CustomerDetailsPreview {...this.props} />
        </div>

        <div className="Form-controls">
          <Button.Transparent
            type="submit"
            onClick={() => {
              this.props.goBack();

              // track.lj.trackReviewScreenBackSuccess();
            }}
          >
            Back
          </Button.Transparent>

          <Button.Primary onClick={this.props.submitPaymentButtonForm}>
            Create Button
          </Button.Primary>
        </div>
      </div>
    );
  }
}
