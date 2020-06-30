import Input from 'common/new-ui/Input';
import { paiseToRupees } from 'common/utils/rzp-utils';
import { getCurrency } from 'common/ui/Amount';

export default class InputCurrencyAmount extends React.Component {
  state = {
    currency: this.props.defaultValueCurrency,
  };

  findDefaultCurrencyOption() {
    const defaultCurrency = this.props.defaultValueCurrency;
  }

  get minAmountAllowed() {
    return paiseToRupees(getCurrency(this.state.currency).min_value);
  }

  get amountFieldName() {
    return this.props.name || 'amount';
  }

  handleChange = data => {
    if (data.hasOwnProperty('currency')) {
      const currencyISO = data.currency.name;

      this.setState({
        currency: currencyISO,
      });
    }

    this.props.onChange && this.props.onChange(data);
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
    } = this.props;

    return (
      <Input.Group
        label={label}
        class={`InputGroup--inline ${className}`}
        required={required}
      >
        <div class="Input-content">
          <Input.CurrencySelect
            name="currency"
            defaultValue={this.state.currency}
            disabled={disabledCurrency}
            onChange={currency =>
              this.handleChange({
                currency,
              })
            }
          />

          <Input
            ref={e => (this.ele = e)}
            class="Input--Amount"
            name={this.amountFieldName}
            placeholder={placeholder}
            description={description}
            type="number"
            defaultValue={defaultValueAmount}
            validator={val => {
              if (val) {
                if (Number(val) < Number(this.minAmountAllowed)) {
                  return `Amount must be at least ${this.minAmountAllowed}`;
                }
              }
            }}
            pattern="^[0-9]+(.([0-9]){1,2})?$"
            onChange={e =>
              this.handleChange({
                [this.amountFieldName]: e.target.value,
              })
            }
            currency={this.state.currency} // This is hack to make this amount Input to re-evaluate error when it's changed
            required={required}
          />
        </div>
      </Input.Group>
    );
  }
}
