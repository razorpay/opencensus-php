import React from 'react';
import Input from 'common/new-ui/Input';
import { i18CurrencyConversionFromMinorUnitToCommonUnit } from 'common/utils/rzp-utils';
import { getCurrency } from 'common/ui/Amount';
import { validateAmount } from 'common/utils/validators';

export default class InputCurrencyAmount extends React.Component {
  state = {
    currency: this.props.defaultValueCurrency,
  };

  findDefaultCurrencyOption() {}

  get minAmountAllowed() {
    const { currency } = this.state;
    return i18CurrencyConversionFromMinorUnitToCommonUnit(
      getCurrency(currency).min_value,
      currency,
    );
  }

  get amountFieldName() {
    return this.props.name || 'amount';
  }

  handleChange = (data) => {
    if (data.hasOwnProperty('currency')) {
      const currencyISO = data.currency.name;

      this.setState({
        currency: currencyISO,
      });
    }

    this.props.onChange && this.props.onChange(data);
  };

  onBlur = (event) => {
    this.props.onBlur &&
      this.props.onBlur({
        currency: this.state.currency,
        value: event.target.value,
      });
  };

  render() {
    const {
      className,
      label,
      description,
      placeholder,
      required,
      defaultValueAmount,
      disabledCurrency,
      disabledAmount,
      autoFocus,
    } = this.props;

    return (
      <Input.Group label={label} className={`InputGroup--inline ${className}`} required={required}>
        <div className="Input-content">
          <Input.CurrencySelect
            name="currency"
            defaultValue={this.state.currency}
            disabled={disabledCurrency}
            onChange={(currency) =>
              this.handleChange({
                currency,
              })
            }
          />

          <Input
            ref={(e) => (this.ele = e)}
            className="Input--Amount"
            name={this.amountFieldName}
            placeholder={placeholder}
            description={description}
            type="number"
            defaultValue={defaultValueAmount}
            validator={(val) => validateAmount(val, this.minAmountAllowed, this.state.currency)}
            onChange={(e) =>
              this.handleChange({
                [this.amountFieldName]: e.target.value,
              })
            }
            currency={this.state.currency} // This is hack to make this amount Input to re-evaluate error when it's changed
            required={required}
            onBlur={this.onBlur}
            autoFocus={autoFocus}
            disabled={disabledAmount}
          />
        </div>
      </Input.Group>
    );
  }
}
