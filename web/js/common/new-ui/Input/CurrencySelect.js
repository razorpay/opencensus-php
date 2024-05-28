import { Component } from 'react';
import { PowerSelect } from 'react-power-select';
import { connect } from 'react-redux';

import ErrorBoundary, { Ranks } from 'common/new-ui/ErrorBoundary';
import { Label } from 'common/new-ui/Input';
import { AmountTooltip } from 'common/ui/Amount';
import { classList, setNativeValue } from 'common/utils/rzp-utils';
import defaultCurrencies from 'merchant/constants/currency';

const frequentlyUsedCurrencies = ['INR', 'USD', 'SGD', 'EUR'];

function currencyOption({ option }, noTick = false) {
  return (
    <div>
      <span>
        <span class="currency-symbol">{option.sym}</span> - {option.label} ({option.name})
      </span>
      {noTick && <i className="i-check text-success" />}
    </div>
  );
}

function OptionComponent({ option }, noTick = false) {
  return (
    <div>
      <span>
        <span class="currency-symbol">{option.sym}</span> - {option.label} ({option.name})
      </span>
      {noTick && <i className="i-check text-success" />}
    </div>
  );
}

function selectedCurrencyOption(option) {
  return (
    <div>
      <span>{option.sym}</span>
    </div>
  );
}

class CurrencySelect extends Component {
  state = this.initState();

  initState() {
    const currencyList = [
      {
        label: 'Frequently Used',
        options: [],
      },
      {
        label: 'All others',
        options: [],
      },
    ];
    let currency;
    const currencies = window.currencyList || defaultCurrencies;
    const isDisabled = this.props.disabled;

    const defaultValue = this.props.defaultValue || 'INR'; // If no value passed, then INR is the displayed option.
    /*
     * Note: it can happen that international is manually disabled (by merchant / by support team).
     * And some payments in international currency might exist, hence regardless international enable, currency requested via this component must reflect correct currency, and not INR.
     * */

    Object.keys(currencies).forEach((isoCurrencyCode) => {
      const fullName = currencies[isoCurrencyCode].name;
      const ISO = isoCurrencyCode;
      const symbol = currencies[isoCurrencyCode].symbol;

      const currencyObj = {
        label: fullName,
        name: ISO,
        sym: symbol,
      };

      if (defaultValue && ISO === defaultValue) {
        currency = currencyObj;
      }

      // If international then populate dropdown options
      if (this.isInternationalEnabled) {
        if (frequentlyUsedCurrencies.indexOf(isoCurrencyCode) > -1) {
          currencyList[0].options.push(currencyObj);
        } else {
          currencyList[1].options.push(currencyObj);
        }
      }
    });
    return {
      currencyList,
      currency,
      disabled: isDisabled,
    };
  }

  onSelectCurrency = ({ option }) => {
    // if valid option selected, then set the currency. If search string returns null, then do nothing
    if (option) {
      this.setState({
        currency: option,
      });

      const inpEle = this.ele;
      setNativeValue(inpEle, option.name);
      inpEle.dispatchEvent(new Event('change', { bubbles: true }));

      if (this.props.onChange) this.props.onChange(option);
    }
  };

  onOpen = () => {
    if (typeof window.hj === 'function') {
      window.hj('trigger', 'international_currency_select');
      window.hj('tagRecording', ['international_currency_select']);
    }

    if (this.props.onOpen) this.props.onOpen();
  };

  getSelectedCurrencyOption = ({ option }) => {
    const optionContent = this.props.fullDisplay
      ? currencyOption({ option }, false)
      : selectedCurrencyOption(option);

    return this.state.disabled ? (
      <AmountTooltip currency={option.name} parentQuerySelector={this.props.parentQuerySelector}>
        {optionContent}
      </AmountTooltip>
    ) : (
      optionContent
    );
  };

  // Note: This considers cases where razorX is enabled but international is manually disabled(by merchant / by support team)
  get isInternationalEnabled() {
    return this.props.user.isInttCurrenciesEnabled;
  }

  render() {
    const props = this.props;

    return (
      <ErrorBoundary
        FallbackComponent={() => <div>Failed to load currency, please try later.</div>}
        rank={Ranks.P0}
        resetOnProps
      >
        <div
          class={classList(
            'Input Input--Currency',
            this.props.fullDisplay && 'Input--Currency--fullDisplay',
            (!this.isInternationalEnabled || this.props.disabled) && 'Input--noMargin',
            this.props.className,
          )}
        >
          {this.props.label && <Label text={this.props.label} />}

          {this.isInternationalEnabled && !this.props.disabled ? (
            <div class="Input-content">
              <div class="Input-elWrapper">
                <div class="Input-el">
                  <input
                    name={props.name || 'currency'}
                    value={this.state.currency.name}
                    hidden
                    readOnly
                    ref={(inp) => (this.ele = inp)}
                  />
                  <PowerSelect
                    name="currency"
                    class="Input--Currency-dropdown ps-in-modal"
                    options={this.state.currencyList}
                    searchIndices={['name', 'label']}
                    placeholder="Select currency"
                    optionComponent={OptionComponent}
                    selectedOptionLabelPath="name"
                    selectedOptionComponent={this.getSelectedCurrencyOption}
                    onChange={this.onSelectCurrency}
                    selected={this.state.currency}
                    showClear={false}
                    searchEnabled
                    onOpen={this.onOpen}
                  />
                </div>
              </div>
            </div>
          ) : (
            <div class="value">
              {props.name && (
                <input name={props.name} value={this.state.currency.name} hidden readOnly />
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
      </ErrorBoundary>
    );
  }
}

export default connect((state) => ({ user: state.session.user }), null)(CurrencySelect);
