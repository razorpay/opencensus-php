import Button from 'common/new-ui/Button';
import ButtonDetailsPreview from '../Preview/Types/ButtonDetailsPreview';
import AmountDetailsPreview from '../Preview/Types/AmountDetailsPreview';
import CustomerDetailsPreview from '../Preview/Types/CustomerDetailsPreview';

import { templateTypes } from 'merchant/views/PaymentButton/PaymentButton/Create/components/Templates/meta';

export default class ReviewAndCreate extends React.Component {
  get isQuickPayTemplate() {
    const { paymentButtonEntity } = this.props;
    const templateType =
      paymentButtonEntity.settings.payment_button_template_type;

    return templateType === templateTypes.quickPay.key;
  }

  render() {
    const { paymentButtonEntity, udfFields, amountFields } = this.props;

    return (
      <div class="Form" style={{ display: this.props.isHidden ? 'none' : '' }}>
        <div class="PaymentButtonForm-ReviewAndCreate Form-content">
          <ButtonDetailsPreview {...this.props} />

          {!this.isQuickPayTemplate && <AmountDetailsPreview {...this.props} />}

          <CustomerDetailsPreview {...this.props} />
        </div>

        <div class="Form-controls">
          <Button.Transparent type="submit" onClick={this.props.goBack}>
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
