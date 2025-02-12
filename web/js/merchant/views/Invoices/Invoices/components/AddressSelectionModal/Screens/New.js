import { Component } from 'react';
import { connect } from 'react-redux';
import { reduxForm } from 'redux-form';
import AsyncButton from 'react-async-button';
import ModalHeader from 'common/ui/ModalHeader';
import Alert from 'common/ui/Forms/Alert';
import * as CustomerActions from 'merchant/reducers/customers';
import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import { fetchStates } from 'merchant/reducers/states';
import AddressEntry from 'merchant/views/Customers/components/AddressEntry';
import PropTypes from 'prop-types';
import { isAddressValid, capitalize } from 'common/utils/rzp-utils';
import { track } from 'merchant/views/Invoices/ga';
import Countries from 'merchant/helpers/countries.json';
import { ORG_CUSTOM_CODE_MAP } from 'merchant/models/User';
import { compose } from 'redux';

const CountryNames = Object.keys(Countries);

const DEFAULT_COUNTY_MAP = {
  [ORG_CUSTOM_CODE_MAP.RAZORPAY]: 'India',
  [ORG_CUSTOM_CODE_MAP.CURLEC]: 'Malaysia',
};

class New extends Component {
  static propTypes = {
    /**
     * Address Type (Shipping/Billing)
     */
    type: PropTypes.string.isRequired,

    /**
     * Customer for whom address is to be added.
     */
    customer: PropTypes.object.isRequired,

    /**
     * Callback invoked after the address is saved.
     */
    onSave: PropTypes.func,

    /**
     * Method invoked when back button is clicked.
     */
    onBackClick: PropTypes.func,

    /**
     * Whether or not to hide the back button.
     */
    hideBack: PropTypes.bool,

    /**
     * Back button text.
     */
    backLabel: PropTypes.string,

    /**
     * Save button text.
     */
    saveLabel: PropTypes.string,

    /**
     * Type of address (billing/shipping).
     */
    addressType: PropTypes.string,
  };

  static defaultProps = {
    onSave: () => {},
    onBackClick: () => {},
    // eslint-disable-next-line react/default-props-match-prop-types
    header: 'Select Address',
    saveLabel: 'Add Address',
    backLabel: 'Back to Addresses',
    hideBack: false,
    addressType: 'billing',
  };

  constructor(props, ...args) {
    super(props, ...args);
    this.DEFAULT_COUNTRY = DEFAULT_COUNTY_MAP[props.org.custom_code] || 'India';

    if (this.props.isInttCurrenciesEnabled) {
      this.state = {
        errors: null,
        editedAddress: {
          country: this.DEFAULT_COUNTRY,
        },
        states: Countries[this.DEFAULT_COUNTRY],
      };
    } else {
      this.state = {
        errors: null,
        editedAddress: {
          country: this.DEFAULT_COUNTRY,
        },
        states: Countries[this.DEFAULT_COUNTRY],
      };
    }
  }

  componentWillUnmount() {
    const { addressType } = this.props;

    track({
      eventAction: `Close Form - Add ${capitalize(addressType)} Address`,
    });
  }

  UNSAFE_componentWillMount() {
    this.props.change('type', this.props.type);
  }

  /**
   * Saves the address and invokes callback.
   * @param {Object} props
   * @return {Promise}
   */
  save = (props) => {
    const { editedAddress } = this.state;

    props = {
      ...props,
      ...editedAddress,
    };

    return this.props
      .addCustomerAddress(this.props.customer, props)
      .then((address) => {
        this.props.onSave(address.data);
        this.props.showNotification({
          type: 'success',
          message: 'Address created successfully',
        });
      })
      .catch((err) => {
        this.setState({ errors: err.errors });

        this.props.trackAddressSelection({
          response: err.errors[1],
          status: 'new',
        });
      });
  };

  /**
   * Update the address in state.
   * @param {Object} address
   */
  onAddressUpdate = (address) => {
    if (this.props.isInttCurrenciesEnabled) {
      let updatedAddress = {
        editedAddress: address,
        states: Countries[address.country],
      };

      if (!address.country) {
        updatedAddress = {
          editedAddress: {
            ...address,
            state: null,
          },
          states: [],
        };
      }

      if (address.country !== this.state.editedAddress.country) {
        updatedAddress.editedAddress.state = null;
      }

      this.setState(updatedAddress);

      return;
    }

    this.setState({
      editedAddress: address,
    });
  };

  onClickClose = () => {
    this.props.onClickClose();

    this.props.closeModal();
  };

  render() {
    const {
      header,
      saveLabel,
      handleSubmit,
      backLabel,
      onBackClick,
      hideBack,
      isInttCurrenciesEnabled,
      trackSelectCountry,
      onBlur,
    } = this.props;

    const { states, editedAddress } = this.state;

    // Boolean that determines if the address is valid or not.
    const invalid = !isAddressValid(editedAddress);

    let extraProps = {};

    if (isInttCurrenciesEnabled) {
      extraProps = {
        countries: CountryNames,
      };
    }

    return (
      <div id="add-address-modal">
        <ModalHeader title={header} onCloseClick={this.onClickClose} />

        <div className="modal-body AddressSelectionModal">
          {!hideBack && (
            <div
              className="text-primary cursor-pointer AddressSelectionModal__header-action"
              onClick={onBackClick}
            >
              <i className="i i-arrow-back" />
              {backLabel}
            </div>
          )}

          <Alert type="error" message={this.state.errors} />

          <div className="AddressSelectionModal__new-headers">
            <label>Enter a new Address</label>
            {!hideBack && (
              <span className="text-primary cursor-pointer" onClick={onBackClick}>
                Cancel
              </span>
            )}
          </div>

          <form onSubmit={handleSubmit(this.save)}>
            <AddressEntry
              onChange={this.onAddressUpdate}
              states={states}
              address={editedAddress}
              showDisabledCountry={true}
              hideCountry={!isInttCurrenciesEnabled}
              trackSelectCountry={trackSelectCountry}
              {...extraProps}
              onBlur={onBlur}
            />

            <div className="row">
              <div className="col-md-12">
                <div className="Modal__actions">
                  <AsyncButton
                    type="submit"
                    className="btn btn-primary btn-block"
                    text={saveLabel}
                    pendingText="Saving..."
                    disabled={invalid}
                    onClick={handleSubmit(this.save)}
                  />
                </div>
              </div>
            </div>
          </form>
        </div>
      </div>
    );
  }
}

export default compose(
  connect((state) => ({ org: state.session.org }), {
    fetchStates,
    ...CustomerActions,
    ...ModalActions,
    ...NotificationsActions,
  }),
  reduxForm({
    form: 'newAddress',
  }),
)(New);
