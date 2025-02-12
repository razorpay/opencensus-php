import React from 'react';
import { connect } from 'react-redux';

import Button from 'common/new-ui/Button';
import Form from 'common/new-ui/Form';
import Input from 'common/new-ui/Input';
import debounce from 'common/utils/debounce';
import {
  updatePaymentButtonData,
  updateAmountField,
  updateStepReviewProgress,
} from 'merchant/reducers/paymentbuttons/create';

import { FieldWithAmountLimits } from '../../AmountDetails/AdvancedForm';

class MainAmountField extends React.Component {
  state = {
    hasDescription: !!this.props.field.description,
  };

  handleToggleAddDescription = () => {
    this.setState({
      hasDescription: !this.state.hasDescription,
    });
  };

  onChange(name, value) {
    // 1.
    this.markReviewUnDone();

    // 2.
    const _amountField = this.props.field;

    const itemFields = ['name', 'description'];
    if (itemFields.indexOf(name) > -1) {
      _amountField.item[name] = value;
    } else {
      _amountField[name] = value;
    }

    console.log(this.props.indexInOrder);
    this.props.updateAmountField(_amountField, this.props.indexInOrder);

    // 3.
    this.props.onChange();
  }

  debounce_onChange = debounce(this.onChange.bind(this), 100);

  handleChange = ({ target }) => {
    const { name, value } = target;

    this.debounce_onChange(name, value);
  };

  onChangeCurrency = (selectedCurrency) => {
    const newCurrencyISO = selectedCurrency.name;

    this.props.updatePaymentButtonData({
      currency: newCurrencyISO,
    });
  };

  markReviewUnDone = () => {
    this.props.updateStepReviewProgress({
      isAmountDetailsReviewed: false,
    });
  };

  render() {
    const { field, currency, isEditExistingId } = this.props;

    const { hasDescription } = this.state;

    return (
      <Form onChange={this.handleChange}>
        <div className="Form-content">
          <Input.Group>
            <Input
              className="Input--vTop"
              label="Field Label"
              name="name"
              required
              placeholder="Enter field label"
              defaultValue={field.item.name}
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
            {hasDescription ? (
              <Input.TextareaAutoResize
                className="Input--description"
                name="description"
                placeholder="Enter field description"
                defaultValue={field.description}
                validator={(val) => {
                  if (val && val.length > 128) {
                    return 'Field description cannot be more than 128 characters';
                  }
                }}
              />
            ) : (
              // Had to had Input.Group for consistency with corresponding Currency Field
              <Input.Group>
                <Button.Transparent type="button" onClick={this.handleToggleAddDescription}>
                  <b>+ Add description</b>
                </Button.Transparent>
              </Input.Group>
            )}
          </Input.Group>

          <Input.Group>
            <Input.CurrencySelect
              label="Donation Currency"
              className="Input--vTop"
              defaultValue={currency}
              disabled={isEditExistingId}
              onChange={this.onChangeCurrency}
              fullDisplay
            />

            <FieldWithAmountLimits
              field={field}
              currency={currency}
              toggleButtonText="+ Add min and max amount limits"
            />
          </Input.Group>
        </div>
      </Form>
    );
  }
}

export default connect(null, {
  updatePaymentButtonData,
  updateAmountField,
  updateStepReviewProgress,
})(MainAmountField);
