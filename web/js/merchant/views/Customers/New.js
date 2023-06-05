import { Component } from 'react';
import { connect } from 'react-redux';
import AsyncButton from 'react-async-button';
import { Field, reduxForm, formValueSelector } from 'redux-form';

import InputField from 'common/ui/Forms/InputField';
import ModalHeader from 'common/ui/ModalHeader';
import Alert from 'common/ui/Forms/Alert';

import { getKeysSeparatedByPipe, isAddressValid, isValidGSTIN } from 'common/utils/rzp-utils';
import { email, phone, validateGSTIN } from 'common/utils/validators';

import * as CustomerActions from 'merchant/reducers/customers';
import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';

import { fetchStates } from 'merchant/reducers/states';
import { ORG_CUSTOM_CODE_MAP } from 'merchant/models/User';
import { HIDDEN_INTERNATIONAL_FEATURES_TAGS } from 'merchant/constants/tags';

import AddressEntry from 'merchant/views/Customers/components/AddressEntry';
import Countries from 'merchant/helpers/countries.json';

const selector = formValueSelector('newCustomer');

const DEFAULT_COUNTY_MAP = {
  [ORG_CUSTOM_CODE_MAP.RAZORPAY]: 'India',
  [ORG_CUSTOM_CODE_MAP.CURLEC]: 'Malaysia',
};

// eslint-disable-next-line react/no-unsafe
@connect(
  (state) => {
    return {
      // Screen 1
      name: selector(state, 'name'),
      email: selector(state, 'email'),
      contact: selector(state, 'contact'),

      shipping_same_as_billing: selector(state, 'shipping_same_as_billing'),
      add_customer_address: selector(state, 'add_customer_address'),
      add_shipping_address: selector(state, 'add_shipping_address'),
      user: state.session.user,
      org: state.session.org,
    };
  },
  {
    fetchStates,
    ...CustomerActions,
    ...ModalActions,
    ...NotificationsActions,
  },
)
@reduxForm({
  form: 'newCustomer',
  validate,
})
export default class AddCustomer extends Component {
  constructor(props) {
    // eslint-disable-next-line prefer-rest-params
    super(...arguments);

    this.DEFAULT_COUNTRY = DEFAULT_COUNTY_MAP[props.org.custom_code] || 'India';

    if (this.props.user.isInttCurrenciesEnabled) {
      this.state = {
        errors: null,
        editedBillingAddress: {
          country: this.DEFAULT_COUNTRY,
        },
        editedShippingAddress: {},
        states: Countries[this.DEFAULT_COUNTRY],
        billingAddressStates: Countries[this.DEFAULT_COUNTRY],
        shippingAddressStates: Countries[this.DEFAULT_COUNTRY],
        screenIndex: 0, // Start on screen 1.
      };
    } else {
      this.state = {
        screenIndex: 0, // Start on screen 1.
        states: Countries[this.DEFAULT_COUNTRY],
        editedBillingAddress: {
          country: this.DEFAULT_COUNTRY,
        },
        editedShippingAddress: {
          country: this.DEFAULT_COUNTRY,
        },
      };
    }
  }

  UNSAFE_componentWillMount() {
    if (this.props.customer) {
      this.props.initialize(this.props.customer);
    }

    if (!this.props.user.isInttCurrenciesEnabled && !this.props.user.isOrgCurlec) {
      const promises = [this.props.fetchStates()];
      this.setState({
        // eslint-disable-next-line react/no-unused-state
        isLoading: true,
      });

      Promise.all(promises)
        .then(([states]) => {
          this.setState({
            // eslint-disable-next-line react/no-unused-state
            isLoading: false,
            states: states && states.data && states.data.items,
          });
        })
        .catch(({ errors }) => {
          this.props.showNotification({
            type: 'error',
            message: errors,
          });
        });
    }
  }

  componentDidMount() {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Customers',
      eventAction: `Open Form - ${
        this.props.customer && this.props.customer.id ? 'Edit' : 'New'
      } Customer`,
    });
  }

  /**
   * Method to change the screen.
   * @param {Integer} screenIndex Screen # to show.
   */
  changeScreen = (screenIndex) => {
    this.setState({
      screenIndex,
    });
  };

  /**
   * Send Analytics event when unmounted.
   */
  componentWillUnmount() {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Customers',
      eventAction: `Close Form - ${
        this.props.customer && this.props.customer.id ? 'Edit' : 'New'
      } Customer`,
    });
  }

  /**
   * Prepare props for saving and return extra props.
   * @param {Object} props
   * @return {Object}
   */

  prepareForSave = (props) => {
    const _props = {};

    // If address is to be saved.
    if (props.add_customer_address) {
      // Bililng Address
      _props.billing_address = this.state.editedBillingAddress;

      // Check if billing and shipping addresses are same despite the checkbox,
      if (!props.shipping_same_as_billing) {
        const shippingAddr = this.state.editedShippingAddress;
        const billingAddr = this.state.editedBillingAddress;
        let areAddressesSame = true;

        // Check if shipping address is the same as billing address.
        if (shippingAddr) {
          Object.keys(billingAddr).forEach((key) => {
            if (billingAddr[key] !== shippingAddr[key]) {
              areAddressesSame = false;
            }
          });
        }

        // Set the flag manually.
        props.shipping_same_as_billing = areAddressesSame;
      }

      // Shipping Address
      // We don't have shipping address if it's supposed to be the same as the billing address.
      if (!props.shipping_same_as_billing) {
        _props.shipping_address = this.state.editedShippingAddress;
      }

      // Remove shipping address if it doesn't need to be added.
      if (!props.add_shipping_address) {
        delete _props.shipping_address;
      }
    }
    return _props;
  };

  /**
   * Saves the customer.
   * @param {Object} props
   * @return {Promise}
   */

  save = (props) => {
    const { shipping_same_as_billing } = props;

    // Analytics.
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Customers',
      eventAction: `Submit Form - ${
        this.props.customer && this.props.customer.id ? 'Edit' : 'New'
      } Customer`,
      eventLabel: getKeysSeparatedByPipe(props),
    });

    // Get extra props.
    const extraProps = this.prepareForSave({ ...props });

    // Save customer.
    return this.props
      .saveCustomer({
        ...props,
        ...extraProps,
      })
      .then((customer) => {
        this.props.onSave(customer, shipping_same_as_billing);
        this.props.showNotification({
          type: 'success',
          message: 'Customer saved successfully',
        });
      })
      .catch((err) => {
        this.setState({
          errors: err.errors,
        });
      });
  };

  /**
   * Method to invoke when Same Shipping address as Billing address is clicked on Screen 3
   */

  onSameShippingAsBilling = (_) => {
    // Get value of checkbox before it was clicked.
    let { shipping_same_as_billing } = this.props;

    // Since it has changed, negate it.
    shipping_same_as_billing = !shipping_same_as_billing;

    // Set shipping address the same as billing address.
    if (shipping_same_as_billing) {
      this.setState((prevState) => {
        return {
          editedShippingAddress: prevState.editedBillingAddress,
          shippingAddressStates: prevState.billingAddressStates,
        };
      });
    }
  };

  /**
   * Unchecks shipping address same as billing address checkbox
   */
  uncheckShippingSameAsBilling = () => {
    this.props.change('shipping_same_as_billing', false);
  };

  /**
   * Toggles the "Add Customer Address" checkbox.
   */

  toggleAddCustomerAddress = (e) => {
    this.setState({
      // eslint-disable-next-line react/no-unused-state
      address: e.target.checked,
    });
  };

  /**
   * Invoked when Billing Address is changed.
   * @param {Object} address
   */

  onBillingAddressChange = (address) => {
    if (this.props.user.isInttCurrenciesEnabled) {
      let updatedAddress = {
        editedBillingAddress: address,
        billingAddressStates: Countries[address.country],
      };

      if (!address.country) {
        updatedAddress = {
          editedBillingAddress: {
            ...address,
            state: null,
          },
          billingAddressStates: [],
        };
      }

      if (address.country !== this.state.editedBillingAddress.country) {
        updatedAddress.editedBillingAddress.state = null;
      }

      this.setState(updatedAddress);

      return;
    }

    this.setState({
      editedBillingAddress: address,
    });
  };

  /**
   * Invoked when Shipping Address is changed/
   * @param {Object} address
   */

  onShippingAddressChange = (address) => {
    this.uncheckShippingSameAsBilling();

    if (this.props.user.isInttCurrenciesEnabled) {
      let updatedAddress = {
        editedShippingAddress: address,
        shippingAddressStates: Countries[address.country],
      };

      if (!address.country) {
        updatedAddress = {
          editedShippingAddress: {
            ...address,
            state: null,
          },
          shippingAddressStates: [],
        };
      }

      if (address.country !== this.state.editedShippingAddress.country) {
        updatedAddress.editedShippingAddress.state = null;
      }

      this.setState(updatedAddress);

      return;
    }

    this.setState({
      editedShippingAddress: address,
    });
  };

  /**
   * Closure/Handler for changeScreen.
   * @param {Number} screenNumber
   * @return {Function}
   */

  getChangeScreenHandler = (screenNumber) => {
    return () => this.changeScreen(screenNumber);
  };

  closeModal = () => {
    this.props.closeModal();

    this.props.onCloseClick && this.props.onCloseClick();
  };

  render() {
    const {
      handleSubmit,
      customer,
      saveLabel,

      name,
      email: _email, // Renaming this because an `email` function is imported for validation.
      contact,

      add_customer_address: address,
      add_shipping_address,

      showGSTN,
      user,
    } = this.props;

    const {
      screenIndex,
      states = [],
      editedBillingAddress,
      editedShippingAddress,
      billingAddressStates,
      shippingAddressStates,
    } = this.state;

    const { isInttCurrenciesEnabled } = user;

    const screens = [];

    /**
     * Array of Booleans that depicts whether or not the CTA on the screen corresponding to the index
     * is supposed to be disabled or not.
     */
    const disabled = [
      // Screen 1
      !(name || _email || contact),

      // Screen 2
      !isAddressValid(editedBillingAddress),

      // Screen 3
      !isAddressValid(editedShippingAddress),
    ];

    // Ask Address only when this is not an Edit Modal or the `add_customer_address` prop is true.
    const askAddress =
      typeof this.props.askAddress === 'undefined'
        ? !(customer && customer.id) || address
        : this.props.askAddress;

    let extraProps = {};

    if (isInttCurrenciesEnabled) {
      extraProps = {
        // eslint-disable-next-line no-use-before-define
        countries: CountryNames,
      };
    }

    const showGSTNInput = showGSTN && !user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.Gst);

    // Add Screen 1
    const screen1 = (
      <div class="modal-body CustomerCreationModal">
        <Alert type="error" message={this.state.errors} />
        <form autoComplete="off">
          <div class="row">
            <div class="col-md-12">
              <div class="form-group">
                <label>Company/Individual Name</label>
                <div>
                  <Field
                    name="name"
                    placeholder="Customer Name"
                    component={InputField}
                    class="form-control"
                    autoFocus={true}
                    onBlur={this.props.onBlur}
                  />
                </div>
              </div>
              <div class="form-group">
                <label>Email</label>
                <div>
                  <Field
                    name="email"
                    placeholder="Email Address"
                    component={InputField}
                    type="email"
                    class="form-control"
                    validate={email('Please provide a valid email')}
                    onBlur={this.props.onBlur}
                  />
                </div>
              </div>
              <div class="form-group">
                <label>Contact No.</label>
                <div>
                  <Field
                    name="contact"
                    placeholder="Contact Number"
                    component={InputField}
                    class="form-control"
                    type="tel"
                    validate={[phone('Invalid Contact')]}
                    onBlur={this.props.onBlur}
                  />
                </div>
              </div>
              {showGSTNInput && (
                <div class="form-group">
                  <label>GSTIN</label>
                  <div>
                    <Field
                      name="gstin"
                      placeholder="e.g 22AAAAA0000A1Z5"
                      component={InputField}
                      class="form-control"
                      validate={[validateGSTIN]}
                      onBlur={this.props.onBlur}
                    />
                  </div>
                </div>
              )}
              {askAddress && (
                <div class="form-group">
                  <div class="rzpCheckbox" style={{ marginTop: '4px' }}>
                    <Field
                      name="add_customer_address"
                      id="add_customer_address"
                      component="input"
                      type="checkbox"
                    />
                    <label
                      for="add_customer_address"
                      style={{ fontWeight: 'normal' }}
                      class="icon i-check"
                    >
                      Add Billing Address
                    </label>
                  </div>
                </div>
              )}
            </div>
          </div>
          {customer && customer.id && (
            <div class="row">
              <div class="col-md-12">
                <p>
                  Note: The updated customer details will be reflected everywhere in the future.
                </p>
              </div>
            </div>
          )}
          <div class="row">
            <div class="col-md-12">
              <div class="Modal__actions">
                <button
                  class="btn btn-primary btn-block"
                  type="button"
                  disabled={disabled[screenIndex]}
                  onClick={address ? this.getChangeScreenHandler(1) : handleSubmit(this.save)}
                >
                  {address ? 'Add Billing Address' : saveLabel}
                </button>
              </div>
            </div>
          </div>
        </form>
      </div>
    );

    screens.push(screen1);

    // Add Screen 2
    screens.push(
      <div class="modal-body CustomerCreationModal">
        <Alert type="error" message={this.state.errors} />
        <form autoComplete="off">
          <div class="row CustomerCreationModal__header-action">
            <div class="col-md-12">
              <span onClick={this.getChangeScreenHandler(0)} class="text-primary cursor-pointer">
                <i class="i i-arrow-back" />
                Back to Customer Details
              </span>
            </div>
          </div>
          <div class="CustomerCreationModal__headers">
            <label>Billing Address</label>
          </div>
          <AddressEntry
            onChange={this.onBillingAddressChange}
            states={isInttCurrenciesEnabled ? billingAddressStates : states}
            address={editedBillingAddress}
            showDisabledCountry={true}
            hideCountry={!isInttCurrenciesEnabled}
            {...extraProps}
          />
          <div class="row CustomerCreationModal__bottom">
            <div class="col-md-12">
              <div>
                <div class="rzpCheckbox">
                  <Field
                    name="add_shipping_address"
                    id="add_shipping_address"
                    component="input"
                    type="checkbox"
                  />
                  <label
                    for="add_shipping_address"
                    style={{ fontWeight: 'normal' }}
                    class="icon i-check"
                  >
                    Add Shipping Address
                  </label>
                </div>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-12">
              <div class="Modal__actions">
                {add_shipping_address ? (
                  <button
                    class="btn btn-primary btn-block"
                    disabled={disabled[screenIndex]}
                    type="button"
                    onClick={this.getChangeScreenHandler(2)}
                  >
                    Add Shipping Address
                  </button>
                ) : (
                  <AsyncButton
                    type="submit"
                    class="btn btn-primary btn-block"
                    text={this.props.saveLabel}
                    pendingText="Saving..."
                    disabled={disabled[screenIndex]}
                    onClick={handleSubmit(this.save)}
                  />
                )}
              </div>
            </div>
          </div>
        </form>
      </div>,
    );

    // Add Screen 3
    screens.push(
      <div class="modal-body CustomerCreationModal">
        <Alert type="error" message={this.state.errors} />
        <form autoComplete="off">
          <div class="row CustomerCreationModal__header-action">
            <div class="col-md-12">
              <span onClick={this.getChangeScreenHandler(1)} class="text-primary cursor-pointer">
                <i class="i i-arrow-back" />
                Back to Billing Address
              </span>
            </div>
          </div>
          <div class="CustomerCreationModal__headers">
            <label>Shipping Address</label>
            <div class="rzpCheckbox">
              <Field
                name="shipping_same_as_billing"
                id="shipping_same_as_billing"
                component="input"
                type="checkbox"
                onChange={this.onSameShippingAsBilling}
              />
              <label
                for="shipping_same_as_billing"
                style={{ fontWeight: 'normal' }}
                class="icon i-check"
              >
                Same as Billing Address
              </label>
            </div>
          </div>
          <AddressEntry
            onChange={this.onShippingAddressChange}
            states={isInttCurrenciesEnabled ? shippingAddressStates : states}
            address={editedShippingAddress}
            showDisabledCountry={true}
            hideCountry={!isInttCurrenciesEnabled}
            {...extraProps}
          />
          <div class="row">
            <div class="col-md-12">
              <div class="Modal__actions">
                <AsyncButton
                  type="submit"
                  class="btn btn-primary btn-block"
                  text={this.props.saveLabel}
                  pendingText="Saving..."
                  disabled={disabled[screenIndex]}
                  onClick={handleSubmit(this.save)}
                />
              </div>
            </div>
          </div>
        </form>
      </div>,
    );

    return (
      <div id="create-customer-modal">
        <ModalHeader
          title={customer && customer.id ? 'Edit Customer' : 'Add Customer'}
          onCloseClick={this.closeModal}
        />

        {screens[screenIndex || 0]}
      </div>
    );
  }
}

AddCustomer.defaultProps = {
  onSave: () => {},
  saveLabel: 'Save',
  showGSTN: true,
};

const CountryNames = Object.keys(Countries);

function validate(values) {
  const errors = {};

  if (values.gstin && !isValidGSTIN(values.gstin)) {
    errors._error = 'Please provide a valid GSTIN';
  }

  return errors;
}
