import { connect } from 'react-redux';
import { PowerSelect } from 'react-power-select';
import { setNativeValue } from 'rzp/utils/rzp-utils';
import { AmountTooltip } from 'rzp/ui/Amount';

// TODO: Move to common var utils list
// NOTE: CSS is effected with index no. change
const defaultCurrencies = [
  {
    label: 'Frequently Used',
    options: [
      {
        label: 'Indian Rupee',
        name: 'INR',
        symbol: '₹',
        flag: 'dummy',
      },
      {
        label: 'US Dollar',
        name: 'USD',
        symbol: 'US$',
        flag: 'dummy',
      },
      {
        label: 'Singapore Dollar',
        name: 'SUSD',
        symbol: 'S$',
        flag: 'dummy',
      },
      {
        label: 'Euro',
        name: 'EUR',
        symbol: '€',
        flag: 'dummy',
      },
    ],
  },
  {
    label: 'All others',
    options: [],
  },
];

function CurrencyOption({ option }) {
  return (
    <div>
      {option.flag && (
        <span class="round-flag">
          <img src="" />
        </span>
      )}
      <span>
        {option.name} - {option.label}
      </span>
      <i className="i-check text-success" />
    </div>
  );
}

function SelectedCurrencyOption(option) {
  return (
    <div>
      <span>{option.symbol}</span>
    </div>
  );
}

@connect(state => ({ user: state.session.user }))
export default class extends React.Component {
  state = this.initState();

  initState() {
    let currencyList = [...defaultCurrencies],
      currency = currencyList[0].options[0], // Selecting first currency in 'Frequently used' group;
      isDisabled = this.props.disabled;

    if (!this.props.user.international) {
      currencyList = [currencyList[0].options[0]]; // Only inr in the list
      isDisabled = true;
    } else {
      const defaultValue = this.props.defaultValue;

      if (defaultValue) {
        let option = currencyList[0].options.filter(
          cur => cur.name === defaultValue
        ); // Check in "Frequently Used"

        if (!option) {
          option = currencyList[1].options.filter(
            cur => cur.name === defaultValue
          ); // Check in "All others"
        }

        if (option.length) {
          currency = option[0];
        }
      }
    }

    return {
      currencyList,
      currency,
      disabled: isDisabled,
    };
  }

  componentWillMount() {
    // Make API call to get the currencies list if doesn't exist
    const moreCurrenciesList = this.props.user.getCurrencyList || [];

    if (this.state.disabled && this.props.user.international) {
      this.state.currencyList[1].options = this.state.currencyList[1].options.concat(
        moreCurrenciesList
      );
      const newCurrencyList = this.state.currencyList;

      this.setState({ currencyList: newCurrencyList });
    }
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
    const optionContent = SelectedCurrencyOption(option);

    return this.state.disabled ? (
      <AmountTooltip
        currency={this.props.currency}
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

    return (
      <div class="Input Input--Currency">
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
                afterOptionsComponent={
                  !props.user.getCurrencyList
                    ? _ => <div>Loading currencies...</div>
                    : undefined
                }
                showClear={false}
                searchEnabled
                disabled={this.state.disabled}
              />
            </div>
          </div>
        </div>
      </div>
    );
  }
}
