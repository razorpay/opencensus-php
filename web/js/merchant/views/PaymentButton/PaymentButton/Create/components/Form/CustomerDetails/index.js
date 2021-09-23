import React from 'react';
import { connect } from 'react-redux';

import Button from 'common/new-ui/Button';
import EditableDisplayField from './EditableDisplayField';

import { getFieldTypes } from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/UDF/helpers';
import { updateStepReviewProgress } from 'merchant/reducers/paymentbuttons/create';
import { templateTypes } from 'merchant/views/PaymentButton/PaymentButton/Create/components/Templates/meta';

import track from '../../../track';

@connect(null, {
  updateStepReviewProgress,
})
export default class CustomerDetails extends React.Component {
  maxFieldsLimit = this.isDonationsTemplate ? 8 : 5;

  get isDonationsTemplate() {
    const { paymentButtonEntity } = this.props;
    const templateType = paymentButtonEntity.settings.payment_button_template_type;

    return templateType === templateTypes.donation.key;
  }

  get defaultNewUDF() {
    const newUDFField = getFieldTypes(true)[0].schema;

    return newUDFField;
  }

  validateSameTitleExists = (title, indexInOrder) => {
    const allFieldsTitles = this.props.udfFields.map((field) => {
      return field.title.toLowerCase();
    });

    const sameTitleIndex = allFieldsTitles.indexOf(title.toLowerCase());

    if (sameTitleIndex > -1 && sameTitleIndex !== indexInOrder) {
      return true;
    }

    return false;
  };

  goNext = () => {
    this.props.goNext();

    this.markReviewDone();

    track.customerScreenNextSuccess();
  };

  markReviewDone = () => {
    this.props.updateStepReviewProgress({
      isCustomerDetailsReviewed: true,
    });
  };

  render() {
    const { udfFields, paymentButtonEntity } = this.props;

    return (
      <div class="Form" style={{ display: this.props.isHidden ? 'none' : '' }}>
        <div class="PaymentButtonForm-CustomerDetails Form-content">
          {udfFields.map((field, index) => (
            <EditableDisplayField
              key={field.title}
              indexInOrder={index}
              field={field}
              checkoutOptions={paymentButtonEntity.settings.checkout_options}
              validateSameTitleExists={this.validateSameTitleExists}
            />
          ))}
          {this.maxFieldsLimit !== udfFields.length && (
            <EditableDisplayField
              field={this.defaultNewUDF}
              checkoutOptions={paymentButtonEntity.settings.checkout_options}
              validateSameTitleExists={this.validateSameTitleExists}
            >
              <Button
                class="Button--primary--invert addFieldBtn"
                onClick={track.customerScreenInputField}
              >
                <b>+ Add Another Input Field</b>
              </Button>
            </EditableDisplayField>
          )}
        </div>

        <div class="Form-controls">
          <Button.Transparent
            type="button"
            onClick={() => {
              this.props.goBack();

              track.customerScreenBackSuccess();
            }}
          >
            Back
          </Button.Transparent>

          <Button.Primary type="button" onClick={this.goNext}>
            Next <i class="i i-chevron-right" />
          </Button.Primary>
        </div>
      </div>
    );
  }
}
