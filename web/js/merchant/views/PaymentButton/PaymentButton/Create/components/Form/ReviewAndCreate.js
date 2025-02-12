import React from 'react';

import Button from 'common/new-ui/Button';
import ButtonDetailsPreview from '../Preview/Types/ButtonDetailsPreview';
import AmountDetailsPreview from '../Preview/Types/AmountDetailsPreview';
import CustomerDetailsPreview from '../Preview/Types/CustomerDetailsPreview';

import { templateTypes } from 'merchant/views/PaymentButton/PaymentButton/Create/components/Templates/meta';

import track from '../../track';

export default class ReviewAndCreate extends React.Component {
  state = {
    isInProgress: false,
  };

  get isQuickPayTemplate() {
    const { paymentButtonEntity } = this.props;
    const templateType = paymentButtonEntity.settings.payment_button_template_type;

    return templateType === templateTypes.quickPay.key;
  }

  handleCreate = () => {
    this.setState({
      isInProgress: true,
    });

    this.props
      .submitPaymentButtonForm()
      .then(() => {
        this.setState({
          isInProgress: false,
        });
      })
      .catch(() => {
        this.setState({
          isInProgress: false,
        });
      });
  };

  render() {
    const { isEditExistingId } = this.props;
    const { isInProgress } = this.state;

    return (
      <div className="Form" style={{ display: this.props.isHidden ? 'none' : '' }}>
        <div className="PaymentButtonForm-ReviewAndCreate Form-content">
          <ButtonDetailsPreview {...this.props} />

          {!this.isQuickPayTemplate && <AmountDetailsPreview {...this.props} />}

          <CustomerDetailsPreview {...this.props} />
        </div>

        <div className="Form-controls">
          <Button.Transparent
            type="submit"
            onClick={() => {
              this.props.goBack();

              track.reviewScreenBackSuccess();
            }}
          >
            Back
          </Button.Transparent>

          <Button.Primary onClick={this.handleCreate} disabled={isInProgress}>
            {isEditExistingId
              ? isInProgress
                ? 'Updating...'
                : 'Update Button'
              : isInProgress
              ? 'Creating...'
              : 'Create Button'}
          </Button.Primary>
        </div>
      </div>
    );
  }
}
