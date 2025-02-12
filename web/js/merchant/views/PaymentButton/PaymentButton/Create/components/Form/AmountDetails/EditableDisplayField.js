import React from 'react';
import { connect } from 'react-redux';

import Input from 'common/new-ui/Input';
import { getCurrency } from 'common/ui/Amount';
import { classList } from 'common/utils/rzp-utils';
import {
  updateAmountField,
  deleteAmountField,
  updatePaymentButtonData,
  updateStepReviewProgress,
} from 'merchant/reducers/paymentbuttons/create';
import track from 'merchant/views/PaymentButton/PaymentButton/Create/track';
import { mapFieldToAmountFieldType } from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/Amount/helpers';
import FIELD_TYPES_MAP from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/Amount/helpers/fieldTypes';

import BaseForm from './BaseForm';
import { DynamicAmount, FixedAmount, FixedAmountWithQuantity } from './FieldTypesRepresentations';

class EditableDisplayField extends React.Component {
  state = {
    isEditModeOpened: this.props.isEditModeOpened || false,
  };

  fieldType = this.props.field ? mapFieldToAmountFieldType(this.props.field) : null;

  componentDidUpdate(prevProps) {
    // This scenario is for while adding new amount field
    if (this.props.hasOwnProperty('isEditModeOpened')) {
      if (prevProps.isEditModeOpened !== this.props.isEditModeOpened) {
        // eslint-disable-next-line react/no-did-update-set-state
        this.setState({
          isEditModeOpened: this.props.isEditModeOpened,
        });

        this.fieldType = this.props.fieldType;
      }
    }
  }

  getDummyAmountInputField(isDynamicAmountField) {
    const { field, currency } = this.props;

    const currencySymbol = getCurrency(currency).symbol;

    return (
      <Input
        label="Value"
        className="Input--vTop Input--dummy Input--withCurrency"
        addonBefore={currencySymbol}
        value={field.item.amount || ''}
        placeholder={isDynamicAmountField ? 'To be filled by cutomer' : ''}
        description={field.item.description ? field.item.description : ''}
        readOnly
      />
    );
  }

  get amountFieldForFieldType() {
    const { field, currency, user } = this.props;
    const countryCode = user.merchant.country_code;
    const FIELD_TYPES = FIELD_TYPES_MAP[countryCode];

    switch (this.fieldType) {
      case FIELD_TYPES.fixed_price.key:
        return (
          <FixedAmount isMandatory={field.mandatory}>{this.getDummyAmountInputField()}</FixedAmount>
        );

      case FIELD_TYPES.dynamic_price.key:
        return (
          <DynamicAmount field={field} currency={currency}>
            {this.getDummyAmountInputField(true)}
          </DynamicAmount>
        );

      case FIELD_TYPES.multiple_purchase.key:
        return (
          <FixedAmountWithQuantity field={field}>
            {this.getDummyAmountInputField()}
          </FixedAmountWithQuantity>
        );
      default:
        return '';
    }
  }

  handleToggleEditMode = () => {
    this.setState((prevState) => {
      return {
        isEditModeOpened: !prevState.isEditModeOpened,
      };
    });

    if (this.props.onClose) this.props.onClose();
  };

  onSubmitBaseForm = (fieldData, currency) => {
    // Update currency for entire payment button entity
    this.props.updatePaymentButtonData({
      currency,
    });

    this.props.updateAmountField(fieldData, this.props.indexInOrder); // If index is undefined, it'll be added as new field

    this.markReviewUnDone();

    track.amountFieldSaveSuccess();
  };

  handleDeleteField = () => {
    this.props.deleteAmountField(this.props.indexInOrder);

    this.markReviewUnDone();

    track.amountFormDeleteField();
  };

  markReviewUnDone = () => {
    this.props.updateStepReviewProgress({
      isAmountDetailsReviewed: false,
    });
  };

  render() {
    const { field, currency, indexInOrder, isEditExistingId, children } = this.props;
    const { isEditModeOpened } = this.state;

    return (
      <div
        onClick={!children && !isEditModeOpened ? this.handleToggleEditMode : () => {}}
        className={classList(
          'EditableAmount EditableDisplayField',
          children && 'EditableDisplayField--disabled',
          isEditModeOpened && 'EditableDisplayField--editMode',
        )}
      >
        {children || (
          <React.Fragment>
            <span className="btn btn-link edit-btn">
              Click to Edit This Item
              <i className="i i-edit" />
            </span>

            <Input
              label="Field Label"
              className="Input--vTop Input--dummy"
              value={field.item.name}
              description={!field.mandatory ? 'Optional' : ''}
              readOnly
            />

            <Input.Group className="">{this.amountFieldForFieldType}</Input.Group>
          </React.Fragment>
        )}

        {isEditModeOpened && (
          <BaseForm
            indexInOrder={indexInOrder} // Index in the ordered schema. If not defined, tells that it's a new field
            field={field}
            fieldType={this.fieldType}
            currency={currency}
            isEditExistingId={isEditExistingId}
            handleClose={this.handleToggleEditMode}
            onSubmit={this.onSubmitBaseForm}
            handleDeleteField={this.handleDeleteField}
            validateSameTitleExists={this.props.validateSameTitleExists}
          />
        )}
      </div>
    );
  }
}

export default connect(
  (state) => ({
    user: state.session.user,
  }),
  {
    updateAmountField,
    deleteAmountField,
    updatePaymentButtonData,
    updateStepReviewProgress,
  },
)(EditableDisplayField);
