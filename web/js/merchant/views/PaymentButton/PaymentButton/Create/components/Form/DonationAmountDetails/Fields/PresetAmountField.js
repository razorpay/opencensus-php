import React from 'react';
import { connect } from 'react-redux';

import Form from 'common/new-ui/Form';
import Input from 'common/new-ui/Input';
import debounce from 'common/utils/debounce';
import {
  updateAmountField,
  updateStepReviewProgress,
} from 'merchant/reducers/paymentbuttons/create';

import InputCurrencyAmount from '../../components/InputCurrencyAmount';

class PresetAmountField extends React.Component {
  onChange(name, value) {
    // 1.
    this.markReviewUnDone();

    // 2.
    const _amountField = this.props.field;

    _amountField.item[name] = value;

    this.props.updateAmountField(_amountField, this.props.indexInOrder);

    // 3.
    this.props.onChange();
  }

  debounce_onChange = debounce(this.onChange.bind(this), 100);

  handleChange = ({ target }) => {
    const { name, value } = target;

    this.debounce_onChange(name, value);
  };

  markReviewUnDone = () => {
    this.props.updateStepReviewProgress({
      isAmountDetailsReviewed: false,
    });
  };

  render() {
    const { field, currency, indexInOrder, disabled } = this.props;

    return (
      <Form onChange={this.handleChange}>
        <div className="Form-content">
          <Input
            className="Input--vTop"
            label="Field Label"
            placeholder="Enter field label"
            name="name"
            defaultValue={field.item.name}
            required
            disabled={disabled}
            validator={(val) => {
              if (!val) {
                return 'Field label is required';
              }

              const regex = new RegExp(`^[0-9a-zA-Z ]+$`, 'i');

              if (!regex.test(val)) {
                return 'Please enter valid value';
              }

              if (!isNaN(val)) {
                return 'Field label must have at least 1 character';
              }

              if (this.props.validateSameTitleExists(val, this.props.indexInOrder)) {
                return 'Field label cannot be same as other field';
              }
            }}
          />

          <InputCurrencyAmount
            key={currency}
            label="Amount"
            name="amount"
            className="Input--vTop Input--CurrencyAmount"
            placeholder="0.00"
            defaultValueCurrency={currency}
            defaultValueAmount={field.item.amount}
            required
            disabledCurrency
            disabledAmount={disabled}
          />

          <span
            className="remove-preset-btn"
            onClick={() => this.props.handleRemovePresetAmountField(indexInOrder)}
          >
            <i className="i i-delete-outline" />
          </span>
        </div>
      </Form>
    );
  }
}

export default connect(null, {
  updateAmountField,
  updateStepReviewProgress,
})(PresetAmountField);
