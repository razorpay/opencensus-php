import Button from 'common/new-ui/Button';
import WidgetPreview from '../Preview/Types/WidgetPreview';
import CustomerDetailsPreview from '../Preview/Types/CustomerDetailsPreview';

import track from '../../track';

export default class ReviewAndCreate extends React.Component {
  render() {
    const { subscriptionButtonEntity, udfFields, planFields } = this.props;

    return (
      <div class="Form" style={{ display: this.props.isHidden ? 'none' : '' }}>
        <div class="PaymentButtonForm-ReviewAndCreate Form-content">
          <WidgetPreview {...this.props} />

          <CustomerDetailsPreview {...this.props} />
        </div>

        <div class="Form-controls">
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
