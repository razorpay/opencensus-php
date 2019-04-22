import { connect } from 'react-redux';
import { PowerSelect } from 'react-power-select';
import { setNativeValue } from 'rzp/utils/rzp-utils';

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

function SelectedCurrencyOption({ option }) {
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
    const currencyList = [...defaultCurrencies];
    const currency = currencyList[0].options[0]; // Selecting first currency in 'Frequently used' group

    return {
      currencyList,
      currency,
    };
  }

  componentWillMount() {
    // Make API call to get the currencies list if doesn't exist
    const moreCurrenciesList = this.props.user.getCurrencyList || [];

    this.state.currencyList[1].options = this.state.currencyList[1].options.concat(
      moreCurrenciesList
    );
    const newCurrencyList = this.state.currencyList;

    this.setState({ currencyList: newCurrencyList });
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

  render() {
    const props = this.props;
    // props: TODO: To handle disabled, required, defaultValue

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
                selectedOptionComponent={SelectedCurrencyOption}
                onChange={this.onSelectCurrency}
                selected={this.state.currency}
                afterOptionsComponent={
                  !props.user.getCurrencyList
                    ? _ => <div>Loading currencies...</div>
                    : undefined
                }
                showClear={false}
                searchEnabled
              />
            </div>
          </div>
        </div>
      </div>
    );
  }
}
