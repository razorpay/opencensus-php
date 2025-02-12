import React from 'react';
import PropTypes from 'prop-types';
import { PowerSelect } from 'react-power-select';
import { connect } from 'react-redux';

import QuickAdd from 'common/ui/Select/QuickAdd';
import State from 'merchant/models/State';
import { ORG_CUSTOM_CODE_MAP } from 'merchant/models/User';

const ZIP_CODE_PLACEHOLDER_MAP = {
  [ORG_CUSTOM_CODE_MAP.RAZORPAY]: 'PIN Code',
  [ORG_CUSTOM_CODE_MAP.CURLEC]: 'POST Code',
};

/**
 * Finds a state from the states-list by it's name.
 * @param {Array} states List of states
 * @param {String} name State to find
 * @return {Object} Returns the state that matches the name
 */
const findStateByName = (states, name) => states.find((s) => s.name === name);

// eslint-disable-next-line react/no-unsafe
class AddressEntry extends React.Component {
  static defaultProps = {
    address: {
      line1: '',
      line2: '',
      zipcode: '',
      city: '',
      state: '',
      country: 'India',
    },
    states: [],
    countries: ['India', 'Malaysia'],
    hideLine2: false,
    hideCountry: false,
    onChange: () => {},
    showDisabledCountry: false,
  };

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

    this.stateInputRef = React.createRef();
  }

  /**
   * Props might be updated from the parent. Update state as well.
   * @param {Object} nextProps The new props
   */
  UNSAFE_componentWillReceiveProps(nextProps) {
    const { address } = nextProps;

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
    this.props.trackSelectCountry && this.props.trackSelectCountry(option); // format: name of country

    // Set state and invoke onChange.
    this.setState(
      {
        country: option,
      },
      () => {
        this.onChange();
      },
    );
  };

  /**
   * Updates the state in PowerSelect.
   */
  updateState = ({ option = {} }) => {
    // Set state and invoke onChange.
    this.setState(
      {
        state: option?.name || null,
      },
      () => {
        this.onChange();
      },
    );
  };

  /**
   * Invokes this.props.onChange with address details.
   */
  onChange = () => {
    const { line1, line2, zipcode, city, state, country } = this.state;

    this.props.onChange({
      line1,
      line2,
      zipcode,
      city,
      state,
      country,
    });
  };

  // eslint-disable-next-line valid-jsdoc
  /**
   * Returns a method to update a field's value stored in state.
   * @param {String} fieldName Key of the field in this.state
   * @return {Function}
   */
  onFieldChangeClosure = (fieldName) => (event) => {
    // Create update object.
    const s = {};
    s[fieldName] = event.target.value;

    // Update state and invoke onChange.
    this.setState(s, () => {
      this.onChange();
    });
  };

  quickCreateState = () => {
    this.setState(
      (prevState) => ({ showStateInput: !prevState.showStateInput }),
      () => {
        if (this.state.showStateInput) {
          this.stateInputRef.current.focus();
        }
      },
    );
  };

  render() {
    const { line1, line2, zipcode, city, state, country, showStateInput } = this.state;

    let { states } = this.props;
    const { onBlur, countries, hideLine2, hideCountry, showDisabledCountry, org } = this.props;

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

    // i18
    const zipCodePlaceholder = ZIP_CODE_PLACEHOLDER_MAP[org.custom_code] || 'Pin Code';

    return (
      <div className="row address-entry">
        <div className="col col-md-12">
          <textarea
            value={line1}
            placeholder="Address line 1 (minimum 10 characters)"
            className="form-control"
            onChange={this.onFieldChangeClosure('line1')}
            autoComplete="address-line1"
            onBlur={onBlur}
          />
        </div>
        {!hideLine2 && (
          <div className="col col-md-12">
            <input
              value={line2}
              placeholder="Address line 2 (Optional, minimum 5 characters)"
              className="form-control"
              type="text"
              onChange={this.onFieldChangeClosure('line2')}
              autoComplete="address-line2"
              onBlur={onBlur}
            />
          </div>
        )}
        <div className="double-field">
          <div className="col col-md-6">
            <input
              value={zipcode}
              placeholder={zipCodePlaceholder}
              className="form-control input-number-no-arrows"
              onChange={this.onFieldChangeClosure('zipcode')}
              autoComplete="postal-code"
              onBlur={onBlur}
            />
          </div>
          <div className="col col-md-6">
            <input
              value={city}
              placeholder="City"
              className="form-control"
              type="text"
              onChange={this.onFieldChangeClosure('city')}
              autoComplete="address-level2"
              onBlur={onBlur}
            />
          </div>
        </div>
        <div className={`${!hideCountry || showDisabledCountry ? 'double-field' : ''}`}>
          <div className={`col col-md-${!hideCountry || showDisabledCountry ? '6' : '12'}`}>
            {showStateInput ? (
              <div className="state-input">
                <input
                  placeholder="State"
                  name="state"
                  defaultValue={selectedState && selectedState.name}
                  className="form-control"
                  ref={this.stateInputRef}
                  onChange={(event) => this.updateState({ option: { name: event.target.value } })}
                />
                <i className="i i-close" onClick={this.quickCreateState} />
              </div>
            ) : (
              <PowerSelect
                placeholder="State"
                className="address-entry-PS ps-in-modal"
                selected={selectedState}
                optionLabelPath="name"
                options={states}
                onChange={this.updateState}
                autoComplete="address-level1"
                labelWhenSearchTermBlank="Create new Customer"
                afterOptionsComponent={(props) => (
                  <QuickAdd {...props} onClick={this.quickCreateState} />
                )}
              />
            )}
          </div>
          {!hideCountry && (
            <div className="col col-md-6">
              <PowerSelect
                placeholder="Country"
                className="address-entry-PS ps-in-modal"
                selected={country}
                options={countries}
                onChange={this.updateCountry}
                autoComplete="country"
              />
            </div>
          )}
          {hideCountry && showDisabledCountry && (
            <div className="col col-md-6">
              <input
                value={country}
                placeholder="Country"
                className="form-control"
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

export default connect((state) => ({ org: state.session.org }))(AddressEntry);
