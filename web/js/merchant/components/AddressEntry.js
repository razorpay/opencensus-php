import { Component } from 'react';
import { PowerSelect } from 'react-power-select';
import PropTypes from 'prop-types';
import State from 'merchant/models/State';

/**
 * Finds a state from the states-list by it's name.
 * @param {Array} states
 * @param {String} name
 * @return {Object}
 */
const findStateByName = (states, name) => states.find(s => s.name === name);

export default class AddressEntry extends Component {
  static propTypes = {
    /**
     * Address object.
     * Keys: line1, line2, zipcode, state, city, country.
     */
    address: PropTypes.object,

    /**
     * Array of State objects.
     */
    states: PropTypes.arrayOf(PropTypes.instanceOf(State)),

    /**
     * Array of Countries
     */
    countries: PropTypes.array,

    /**
     * Whether or not to hide the line 2 field.
     * For the times when Address line 2 is not required.
     */
    hideLine2: PropTypes.bool,

    /**
     * Whether or not to hide the Country field.
     * For times when Country is not required.
     */
    hideCountry: PropTypes.bool,

    /**
     * onChange handler for the address object.
     * @param {Object} address
     */
    onChange: PropTypes.func,

    /**
     * Show disabled India input as country field.
     */
    showDisabledCountry: PropTypes.bool,
  };

  constructor(props) {
    super(props);

    this.state = {
      ...AddressEntry.defaultProps.address,
      ...props.address,
    };
  }

  /**
   * Props might be updated from the parent. Update state as well.
   * @param {Object} nextProps
   */
  componentWillReceiveProps(nextProps) {
    let { address } = nextProps;

    if (address) {
      this.setState({
        ...address,
      });
    }
  }

  /**
   * Updates the country in PowerSelect.
   */
  updateCountry = ({ option }) => {
    // Set state and invoke onChange.
    this.setState(
      {
        country: option,
      },
      () => {
        this.onChange();
      }
    );
  };

  /**
   * Updates the state in PowerSelect.
   */
  updateState = ({ option = {} }) => {
    // Set state and invoke onChange.
    this.setState(
      {
        state: option.name || null,
      },
      () => {
        this.onChange();
      }
    );
  };

  /**
   * Invokes this.props.onChange with address details.
   */
  onChange = () => {
    let { line1, line2, zipcode, city, state, country } = this.state;

    this.props.onChange({
      line1,
      line2,
      zipcode,
      city,
      state,
      country,
    });
  };

  /**
   * Returns a method to update a field's value stored in state.
   * @param {String} fieldName Key of the field in this.state
   * @return {Function}
   */
  onFieldChangeClosure = fieldName => event => {
    // Create update object.
    let s = {};
    s[fieldName] = event.target.value;

    // Zipcode can be of 6 chars at most.
    if (fieldName === 'zipcode') {
      if (s[fieldName]) {
        s[fieldName] = s[fieldName].slice(0, 6);
      }
    }

    // Update state and invoke onChange.
    this.setState(s, () => {
      this.onChange();
    });
  };

  render() {
    const { line1, line2, zipcode, city, state, country } = this.state;

    let {
      states,
      countries,
      hideLine2,
      hideCountry,
      showDisabledCountry,
    } = this.props;

    // Get the State.
    let selectedState = null;

    // If the States array has objects and State is given, find the State object.
    if (states.length && state) {
      selectedState = findStateByName(states, state);
    } else if (!states.length && state) {
      /**
       * If the States array is empty (probably because the n/w request hasn't been completed),
       * and a State is provided from the props, create a list with the State and show it.
       * We're doing this because we don't want the UI to show a blank State until the n/w request to fetch
       * States is finished.
       */
      states = [state];
      selectedState = state;
    }

    return (
      <div class="row address-entry">
        <div class="col col-md-12">
          <textarea
            value={line1}
            placeholder="Address line 1 (minimum 10 characters)"
            class="form-control"
            onChange={this.onFieldChangeClosure('line1')}
            autoComplete="address-line1"
          />
        </div>
        {!hideLine2 && (
          <div class="col col-md-12">
            <input
              value={line2}
              placeholder="Address line 2 (Optional, minimum 5 characters)"
              class="form-control"
              type="text"
              onChange={this.onFieldChangeClosure('line2')}
              autoComplete="address-line2"
            />
          </div>
        )}
        <div class="double-field">
          <div class="col col-md-6">
            <input
              value={zipcode}
              placeholder="PIN Code"
              class="form-control input-number-no-arrows"
              type="number"
              onChange={this.onFieldChangeClosure('zipcode')}
              autoComplete="postal-code"
            />
          </div>
          <div class="col col-md-6">
            <input
              value={city}
              placeholder="City"
              class="form-control"
              type="text"
              onChange={this.onFieldChangeClosure('city')}
              autoComplete="address-level2"
            />
          </div>
        </div>
        <div
          class={`${!hideCountry || showDisabledCountry ? 'double-field' : ''}`}
        >
          <div
            class={`col col-md-${
              !hideCountry || showDisabledCountry ? '6' : '12'
            }`}
          >
            <PowerSelect
              placeholder="State"
              class="address-entry-PS ps-in-modal"
              selected={selectedState}
              optionLabelPath="name"
              options={states}
              onChange={this.updateState}
              autoComplete="address-level1"
            />
          </div>
          {!hideCountry && (
            <div class="col col-md-6">
              <PowerSelect
                placeholder="Country"
                class="address-entry-PS ps-in-modal"
                selected={country}
                options={countries}
                onChange={this.updateCountry}
                autoComplete="country"
              />
            </div>
          )}
          {hideCountry &&
            showDisabledCountry && (
              <div class="col col-md-6">
                <input
                  value="India"
                  placeholder="Country"
                  class="form-control"
                  type="text"
                  disabled
                />
              </div>
            )}
        </div>
      </div>
    );
  }
}

AddressEntry.defaultProps = {
  address: {
    line1: '',
    line2: '',
    zipcode: '',
    city: '',
    state: '',
    country: 'India',
  },
  states: [],
  countries: ['India'],
  hideLine2: false,
  hideCountry: false,
  onChange: () => {},
  showDisabledCountry: false,
};
