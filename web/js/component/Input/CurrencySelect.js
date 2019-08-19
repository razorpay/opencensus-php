import { connect } from 'react-redux';
import { PowerSelect } from 'react-power-select';
import { setNativeValue } from 'rzp/utils/rzp-utils';
import { AmountTooltip } from 'rzp/ui/Amount';
import { classList } from 'common/util';

const frequentlyUsedCurrencies = ['INR', 'USD', 'SGD', 'EUR'];

function CurrencyOption({ option }, noTick = false) {
  return (
    <div>
      <span>
        <span class="currency-symbol">{option.sym}</span> - {option.label} ({
          option.name
        })
      </span>
      {noTick && <i className="i-check text-success" />}
    </div>
  );
}

function SelectedCurrencyOption(option) {
  return (
    <div>
      <span>{option.sym}</span>
    </div>
  );
}

@connect(state => ({ user: state.session.user }))
export default class extends React.Component {
  state = this.initState();

  initState() {
    let currencyList = [
        {
          label: 'Frequently Used',
          options: [],
        },
        {
          label: 'All others',
          options: [],
        },
      ],
      currency,
      isDisabled = this.props.disabled;

    if (this.props.user.international) {
      const defaultValue = this.props.defaultValue;

      Object.keys(window.currencyList).forEach(c => {
        const fullName = window.currencyList[c].name,
          ISO = c,
          symbol = window.currencyList[c].symbol;

        const currencyObj = {
          label: fullName,
          name: ISO,
          sym: symbol,
        };

        if (defaultValue && ISO === defaultValue) {
          currency = currencyObj;
        }

        if (frequentlyUsedCurrencies.indexOf(c) > -1) {
          currencyList[0].options.push(currencyObj);
        } else {
          currencyList[1].options.push(currencyObj);
        }
      });
    }

    this.INR_option = {
      label: window.currencyList['INR'].full_name,
      name: 'INR',
      sym: window.currencyList['INR'].symbol,
    };

    if (!currency) {
      currency = this.INR_option; // default option if no defaultValue set by parent
    }

    return {
      currencyList,
      currency,
      disabled: isDisabled,
    };
  }

  onSelectCurrency = ({ option }) => {
    this.setState({
      currency: option,
    });

    const inpEle = this.ele;
    setNativeValue(inpEle, option.name);
    inpEle.dispatchEvent(new Event('change', { bubbles: true }));

    this.props.onChange && this.props.onChange(option);
  };

  getSelectedCurrencyOption = ({ option }) => {
    const optionContent = this.props.fullDisplay
      ? CurrencyOption({ option }, false)
      : SelectedCurrencyOption(option);

    return this.state.disabled ? (
      <AmountTooltip
        currency={option.name}
        parentQuerySelector={this.props.parentQuerySelector}
      >
        {optionContent}
      </AmountTooltip>
    ) : (
      optionContent
    );
  };

  render() {
    const props = this.props;

    const isInternationalEnabled = this.props.user.isInttCurrenciesEnabled;

    return (
      <div
        class={classList(
          'Input Input--Currency',
          this.props.fullDisplay && 'Input--Currency--fullDisplay',
          (!isInternationalEnabled || this.props.disabled) && 'Input--noMargin'
        )}
      >
        {isInternationalEnabled && !this.props.disabled ? (
          <div class="Input-content">
            <div class="Input-elWrapper">
              <div class="Input-el">
                <input
                  name={props.name || 'currency'}
                  value={this.state.currency.name}
                  hidden
                  readOnly
                  ref={inp => (this.ele = inp)}
                />
                <PowerSelect
                  name="currency"
                  class="Input--Currency-dropdown ps-in-modal"
                  options={this.state.currencyList}
                  searchIndices={['name', 'label']}
                  placeholder="Select currency"
                  optionComponent={CurrencyOption}
                  selectedOptionLabelPath="name"
                  selectedOptionComponent={this.getSelectedCurrencyOption}
                  onChange={this.onSelectCurrency}
                  selected={this.state.currency}
                  showClear={false}
                  searchEnabled
                />
              </div>
            </div>
          </div>
        ) : (
          <div class="value">
            {props.name && (
              <input
                name={props.name}
                value={this.state.currency.name}
                hidden
                readOnly
              />
            )}
            <AmountTooltip
              currency={this.state.currency.name}
              parentQuerySelector={this.props.parentQuerySelector}
            >
              {this.state.currency.sym}
            </AmountTooltip>
          </div>
        )}
      </div>
    );
  }
}
