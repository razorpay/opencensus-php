import React from 'react';
import { connect } from 'react-redux';

import Button from 'common/new-ui/Button';
import { updateStepReviewProgress } from 'merchant/reducers/subscriptionButtons/create';
import { getFieldTypes } from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/UDF/helpers';

import EditableDisplayField from './EditableDisplayField';
import track from '../../../track';

class CustomerDetails extends React.Component {
  maxFieldsLimit = 5;

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
  };

  goNext = () => {
    this.props.goNext();

    this.markReviewDone();

    track.lj.trackCustomerScreenNextSuccess();
  };

  markReviewDone = () => {
    this.props.updateStepReviewProgress({
      isCustomerDetailsReviewed: true,
    });
  };

  render() {
    const { udfFields, subscriptionButtonEntity } = this.props;

    return (
      <div className="Form" style={{ display: this.props.isHidden ? 'none' : '' }}>
        <div className="PaymentButtonForm-CustomerDetails Form-content">
          {udfFields.map((field, index) => (
            <EditableDisplayField
              key={field.title}
              indexInOrder={index}
              field={field}
              checkoutOptions={subscriptionButtonEntity.settings.checkout_options}
              validateSameTitleExists={this.validateSameTitleExists}
            />
          ))}
          {this.maxFieldsLimit !== udfFields.length && (
            <EditableDisplayField
              field={this.defaultNewUDF}
              checkoutOptions={subscriptionButtonEntity.settings.checkout_options}
              validateSameTitleExists={this.validateSameTitleExists}
            >
              <Button
                className="Button--primary--invert addFieldBtn"
                onClick={track.lj.trackCustomerScreenInputField}
              >
                <b>+ Add Another Input Field</b>
              </Button>
            </EditableDisplayField>
          )}
        </div>

        <div className="Form-controls">
          <Button.Transparent
            type="button"
            onClick={() => {
              this.props.goBack();

              track.lj.trackCustomerScreenBackSuccess();
            }}
          >
            Back
          </Button.Transparent>

          <Button.Primary type="button" onClick={this.goNext}>
            Next <i className="i i-chevron-right" />
          </Button.Primary>
        </div>
      </div>
    );
  }
}

export default connect(null, {
  updateStepReviewProgress,
})(CustomerDetails);
