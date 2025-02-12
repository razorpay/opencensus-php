import React from 'react';
import { connect } from 'react-redux';

import Input from 'common/new-ui/Input';
import { classList } from 'common/utils/rzp-utils';
import {
  updateUDFField,
  deleteUDFField,
  updatePaymentButtonData,
  updateStepReviewProgress,
} from 'merchant/reducers/subscriptionButtons/create';
import {
  getFieldTypes,
  constructFieldSchema,
  mapFieldToIndex,
} from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/UDF/helpers';

import BaseForm from './BaseForm';
import track from '../../../track';

class EditableDisplayField extends React.Component {
  state = {
    isEditModeOpened: false,
  };

  fieldTypeOptions = getFieldTypes(true);

  handleToggleEditMode = () => {
    this.setState({
      isEditModeOpened: !this.state.isEditModeOpened,
    });
  };

  onSubmitBaseForm = (fieldData) => {
    const newFieldSchema = constructFieldSchema(fieldData);

    if (!newFieldSchema || (newFieldSchema.enum && (!fieldData.enum || !fieldData.enum.length))) {
      throw 'Invalid field data';
    }

    if (newFieldSchema.enum) {
      newFieldSchema.enum = fieldData.enum.concat();
    }

    // Remap the email and phone in checkout_options as per their updated label
    if (this.isCheckoutOption) {
      const { checkoutOptions } = this.props;
      checkoutOptions[newFieldSchema.pattern] = newFieldSchema.name; // Update the key

      this.props.updatePaymentButtonData({
        settings: { checkout_options: checkoutOptions },
      });
    }

    this.props.updateUDFField(newFieldSchema, this.props.indexInOrder); // If index is undefined, it'll be added as new field

    this.markReviewUnDone();

    track.lj.trackCustomerScreenFieldSaveSuccess();
  };

  handleDeleteField = () => {
    this.props.deleteUDFField(this.props.indexInOrder);

    this.markReviewUnDone();

    track.lj.trackCustomerScreenDeleteField();
  };

  get isCheckoutOption() {
    const { field, checkoutOptions } = this.props;

    if (field && field.name) {
      if ([checkoutOptions.email, checkoutOptions.phone].indexOf(field.name) > -1) {
        return true;
      }
    }

    return false;
  }

  findSelectedOptionInFieldTypes() {
    const { field } = this.props;
    const index = mapFieldToIndex(field);

    return this.fieldTypeOptions[index];
  }

  markReviewUnDone = () => {
    this.props.updateStepReviewProgress({
      isCustomerDetailsReviewed: false,
    });
  };

  render() {
    const { field, children, indexInOrder } = this.props;
    const { isEditModeOpened } = this.state;

    let isFieldForcedRequired = false; // If so, then no option in dropdown to set the field optional.

    if (this.isCheckoutOption) {
      isFieldForcedRequired = true; // Email and Phone cannot be made as Optional field
    }

    const selectedOptionInFieldTypes = this.findSelectedOptionInFieldTypes();

    return (
      <div
        onClick={!isEditModeOpened ? this.handleToggleEditMode : () => {}}
        className={classList(
          'EditableUDF EditableDisplayField',
          children && 'EditableDisplayField--disabled',
          isEditModeOpened && 'EditableDisplayField--editMode',
        )}
      >
        {children || (
          <React.Fragment>
            <span className="btn btn-link edit-btn">
              Click to Edit This Field
              <i className="i i-edit" />
            </span>

            <Input
              label="Field Type"
              className="Input--vTop Input--dummy"
              value={selectedOptionInFieldTypes.label}
              description={!field.required ? 'Optional Field' : ''}
              readOnly
            />

            <Input
              label="Field Label"
              className="Input--vTop Input--dummy"
              value={field.title}
              description={field.description ? field.description : ''}
              readOnly
            />
          </React.Fragment>
        )}

        {isEditModeOpened && (
          <BaseForm
            indexInOrder={indexInOrder} // Index in the ordered schema. If not defined, tells that it's a new field
            field={field}
            isFieldForcedRequired={isFieldForcedRequired}
            selectedOptionInFieldTypes={selectedOptionInFieldTypes}
            handleDeleteField={!isFieldForcedRequired ? this.handleDeleteField : undefined} // TODO: For email and phone cannot be deleted
            handleClose={this.handleToggleEditMode}
            onSubmit={this.onSubmitBaseForm}
            validateSameTitleExists={this.props.validateSameTitleExists}
          />
        )}
      </div>
    );
  }
}

export default connect(null, {
  updateUDFField,
  deleteUDFField,
  updatePaymentButtonData,
  updateStepReviewProgress,
})(EditableDisplayField);
