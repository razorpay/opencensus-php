import React from 'react';
import { connect } from 'react-redux';

import { Description } from 'common/new-ui/Input';
import SwitchField from 'common/ui/Forms/SwitchField';
import Button from 'common/new-ui/Button';
import MainAmountField from './Fields/MainAmountField';
import PresetAmountField from './Fields/PresetAmountField';

import { getCurrency } from 'common/ui/Amount';
import { paiseToRupees } from 'common/utils/rzp-utils';
import debounce from 'common/utils/debounce';
import FIELD_TYPES from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/Amount/helpers/fieldTypes';
import { getBaseFieldForAmountFieldType } from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/Amount/helpers';
import {
  updateAmountField,
  deleteAmountField,
  updateStepReviewProgress,
} from 'merchant/reducers/paymentbuttons/create';

import track from '../../../track';

@connect(null, {
  updateAmountField,
  deleteAmountField,
  updateStepReviewProgress,
})
export default class AmountDetails extends React.Component {
  maxItemsLimit = 5;
  state = {
    disableSubmit: false,
    hasPresetAmountFields: this.props.amountFields && this.props.amountFields.length > 1,
  };

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

    track.donationScreenNextSuccess();
  };

  markReviewDone = () => {
    this.props.updateStepReviewProgress({
      isAmountDetailsReviewed: true,
    });
  };

  onChange = () => {
    setTimeout(this.toggleSubmitBtn); // Validate form for input errors via class change in DOM, hence delayed.
  };

  handleAddPresetAmountField = (presetIndexInOrder) => {
    if (typeof presetIndexInOrder !== 'number') {
      const totalPresets = this.props.amountFields.length - 1;
      presetIndexInOrder = totalPresets + 1;
    }

    const newAmountField = getBaseFieldForAmountFieldType(FIELD_TYPES.fixed_price.key);
    newAmountField.mandatory = false; // Optional
    newAmountField.item.amount = presetIndexInOrder * 500; // Note: Adding some amount is important otherwise it would get treated as dynamic amount field in Preview

    newAmountField.item.name = `SUPPORT FOR CAUSE ${presetIndexInOrder}`;

    newAmountField.uniqueKey = new Date().getTime() * presetIndexInOrder;

    this.props.updateAmountField(newAmountField);
    setTimeout(this.toggleSubmitBtn);
  };

  handleRemovePresetAmountField = (indexInOrder) => {
    this.props.deleteAmountField(indexInOrder);
  };

  addRemovePresetAmountFields() {
    const { amountFields } = this.props;

    if (!this.state.hasPresetAmountFields) {
      // Remove all the presets

      const totalPresets = amountFields.length - 1; // Skip the main field

      for (let i = 1; i <= totalPresets; i++) {
        const indexInOrder = 1; // Note: presetIndex will always be 1, bcoz fields are getting removed one-by-one
        this.handleRemovePresetAmountField(indexInOrder);
      }
    } else if (amountFields.length == 1) {
      // Add 2 presets by default

      this.handleAddPresetAmountField(1);
      this.handleAddPresetAmountField(2);
    }
  }

  debounce_addRemovePresetAmountFields = debounce(this.addRemovePresetAmountFields.bind(this), 400);

  toggleMainAmountFieldMandatory = () => {
    const mainAmountField = this.props.amountFields[0];

    if (this.state.hasPresetAmountFields) {
      mainAmountField.mandatory = false;
      mainAmountField.min_amount = 0;
    } else {
      mainAmountField.mandatory = true;

      const currency = this.props.paymentButtonEntity.currency;
      // Dealing with rupees(bigger currency) in UI. Converted to paisa only when sent to API.
      const minAmountAllowed = paiseToRupees(getCurrency(currency).min_value);

      mainAmountField.min_amount = minAmountAllowed;
    }

    this.props.updateAmountField(mainAmountField, 0); // Index of main amount field is always 0
  };

  toggleAddPresetAmountFields = () => {
    // eslint-disable-next-line react/no-access-state-in-setstate
    const hasPresetAmountFields = !this.state.hasPresetAmountFields;

    this.setState(
      {
        hasPresetAmountFields,
      },
      () => {
        // 1.
        this.toggleMainAmountFieldMandatory();

        // 2.
        // Instantly add the fields, but have delay while removing fields so that data is retained in accidental clicks
        if (hasPresetAmountFields) {
          this.addRemovePresetAmountFields();
        } else {
          this.debounce_addRemovePresetAmountFields();
        }
      },
    );
  };

  toggleSubmitBtn = () => {
    const form = this.formContainerEl;
    const disableSubmit = !!form.querySelectorAll('.is-invalid').length;

    this.setState({ disableSubmit });
  };

  setRefFormContainer = (el) => (this.formContainerEl = el);

  render() {
    const { amountFields, paymentButtonEntity } = this.props;
    const currency = paymentButtonEntity.currency;

    const { hasPresetAmountFields } = this.state;

    const presetAmountFields = amountFields.slice(1);

    return (
      <div
        class="Form"
        style={{ display: this.props.isHidden ? 'none' : '' }}
        ref={this.setRefFormContainer}
      >
        <div class="PaymentButtonForm-AmountDetails PaymentButtonForm-AmountDetails--donation Form-content">
          <MainAmountField
            field={amountFields[0]}
            currency={currency}
            indexInOrder={0} // main amount field will be 0 as fixed
            validateSameTitleExists={this.validateSameTitleExists}
            onChange={this.onChange}
          />

          <div class="togglePresetsContainer">
            <div>
              <SwitchField
                type="prime round"
                checked={hasPresetAmountFields}
                onChange={this.toggleAddPresetAmountFields}
              />
              <b class="m-l">Show presets for donation amount</b>
            </div>

            <Description text="Your supporters can either choose to enter custom amount or pick a preset amount" />
          </div>

          {/* Not adding check for hasPresetAmountFields bcoz of debounce feature */}
          {presetAmountFields.map((field, index) => {
            return (
              <PresetAmountField
                key={field.uniqueKey}
                indexInOrder={index + 1} // Accounting index of the main amount field
                field={field}
                currency={currency}
                validateSameTitleExists={this.validateSameTitleExists}
                onChange={this.onChange}
                handleRemovePresetAmountField={this.handleRemovePresetAmountField}
                disabled={!hasPresetAmountFields}
              />
            );
          })}

          {/* Not adding check for hasPresetAmountFields bcoz of debounce feature */}
          {amountFields.length > 1 && amountFields.length < this.maxItemsLimit && (
            <div>
              <Button.Transparent
                onClick={this.handleAddPresetAmountField}
                disabled={!hasPresetAmountFields}
              >
                <b>+ Add Another Preset</b>
              </Button.Transparent>
            </div>
          )}
        </div>

        <div class="Form-controls">
          <Button.Transparent
            type="button"
            onClick={() => {
              this.props.goBack();

              track.donationScreenBackSuccess();
            }}
          >
            Back
          </Button.Transparent>

          <Button.Primary type="button" onClick={this.goNext} disabled={this.state.disableSubmit}>
            Next <i class="i i-chevron-right" />
          </Button.Primary>
        </div>
      </div>
    );
  }
}
