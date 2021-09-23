import React from 'react';
import { connect } from 'react-redux';

import Button from 'common/new-ui/Button';
import EditableDisplayField from './EditableDisplayField';
import FieldsDropdown from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/FieldsDropdown';

import { templateTypes } from 'merchant/views/PaymentButton/PaymentButton/Create/components/Templates/meta';
import { getBaseFieldForAmountFieldType } from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/Amount/helpers';
import FIELD_TYPES from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/Amount/helpers/fieldTypes';
import { updateStepReviewProgress } from 'merchant/reducers/paymentbuttons/create';

import track from '../../../track';

@connect(null, {
  updateStepReviewProgress,
})
export default class AmountDetails extends React.Component {
  constructor(props) {
    super(props);

    this.maxItemsLimit = 5;

    const { paymentButtonEntity } = props;
    const templateType = paymentButtonEntity.settings.payment_button_template_type;
    let allowedAmountTypesList;

    if (templateType === templateTypes.buyNow.key) {
      allowedAmountTypesList = [FIELD_TYPES.fixed_price, FIELD_TYPES.multiple_purchase];
    } else if (templateType === templateTypes.donation.key) {
      allowedAmountTypesList = [FIELD_TYPES.fixed_price, FIELD_TYPES.dynamic_price];
    } else {
      allowedAmountTypesList = [
        FIELD_TYPES.fixed_price,
        FIELD_TYPES.dynamic_price,
        FIELD_TYPES.multiple_purchase,
      ];
    }

    this.allowedAmountTypesList = allowedAmountTypesList;
  }

  validateSameTitleExists = (title, indexInOrder) => {
    const allFieldsTitles = this.props.amountFields.map((field) => {
      return field.item.name.toLowerCase();
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

    track.amountScreenNextSuccess();
  };

  markReviewDone = () => {
    this.props.updateStepReviewProgress({
      isAmountDetailsReviewed: true,
    });
  };

  render() {
    const { amountFields, paymentButtonEntity } = this.props;
    const currency = paymentButtonEntity.currency;

    return (
      <div class="Form" style={{ display: this.props.isHidden ? 'none' : '' }}>
        <div class="PaymentButtonForm-AmountDetails Form-content">
          {amountFields.map((field, index) => (
            <EditableDisplayField
              key={field.item.name}
              indexInOrder={index}
              field={field}
              currency={currency}
              validateSameTitleExists={this.validateSameTitleExists}
            />
          ))}
          {this.maxItemsLimit !== amountFields.length && (
            <AddAmountFieldButton
              currency={currency}
              validateSameTitleExists={this.validateSameTitleExists}
              allowedAmountTypesList={this.allowedAmountTypesList}
            />
          )}
        </div>

        <div class="Form-controls">
          <Button.Transparent
            type="button"
            onClick={() => {
              this.props.goBack();

              track.amountScreenBackSuccess();
            }}
          >
            Back
          </Button.Transparent>

          <Button.Primary type="button" disabled={!amountFields.length} onClick={this.goNext}>
            Next <i class="i i-chevron-right" />
          </Button.Primary>
        </div>
      </div>
    );
  }
}

class AddAmountFieldButton extends React.Component {
  state = {
    newAmountField: null,
    newAmountFieldType: null,
    isEditModeOpened: false,
  };

  onSelectAmountType = (amountOption) => {
    const newAmountFieldType = amountOption.key;
    const newAmountField = getBaseFieldForAmountFieldType(newAmountFieldType);

    this.setState({
      isEditModeOpened: true,
      newAmountField,
      newAmountFieldType,
    });
  };

  handleCloseEditMode = () => {
    this.setState({
      isEditModeOpened: false,
    });
  };

  render() {
    const { newAmountField, newAmountFieldType, isEditModeOpened } = this.state;

    const { currency, allowedAmountTypesList } = this.props;

    return (
      <EditableDisplayField
        field={newAmountField}
        fieldType={newAmountFieldType}
        currency={currency}
        isEditModeOpened={isEditModeOpened}
        validateSameTitleExists={this.props.validateSameTitleExists}
        onClose={this.handleCloseEditMode}
      >
        <FieldsDropdown
          beforeOptionsTxt="Select Amount Type"
          type="amount"
          options={allowedAmountTypesList}
          trigger={
            <Button class="Button--primary--invert addFieldBtn" onClick={track.onClickAmountField}>
              <b>+ Add Amount Field</b>
            </Button>
          }
          onSelect={this.onSelectAmountType}
          showInfo
        />
      </EditableDisplayField>
    );
  }
}
