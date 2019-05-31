import { Component } from 'react';
import { connect } from 'react-redux';
import { reduxForm, formValueSelector } from 'redux-form';
import AsyncButton from 'react-async-button';
import ModalHeader from 'rzp/ui/ModalHeader';
import Alert from 'rzp/ui/Forms/Alert';
import * as CustomerActions from 'merchant/modules/customers';
import * as ModalActions from 'rzp/modules/modals';
import * as NotificationsActions from 'rzp/modules/notifications';
import { fetchStates } from 'merchant/modules/states';
import AddressEntry from 'merchant/components/AddressEntry.js';
import PropTypes from 'prop-types';
import { isAddressValid, capitalize } from 'rzp/utils/rzp-utils';
import { track } from '../../ga';

@connect(state => ({}), {
  fetchStates,
  ...CustomerActions,
  ...ModalActions,
  ...NotificationsActions,
})
@reduxForm({
  form: 'newAddress',
})
export default class New extends Component {
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
     * Modal title.
     */
    header: PropTypes.string.isRequired,

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
    header: 'Select Address',
    saveLabel: 'Add Address',
    backLabel: 'Back to Addresses',
    hideBack: false,
    addressType: 'billing',
  };

  constructor() {
    super(...arguments);
    this.state = {
      errors: null,
    };
  }

  componentWillUnmount() {
    const { addressType } = this.props;

    track({
      eventAction: `Close Form - Add ${capitalize(addressType)} Address`,
    });
  }

  componentWillMount() {
    // Fetch states.
    let promises = [this.props.fetchStates()];
    this.setState({
      isLoading: true,
    });

    Promise.all(promises)
      .then(([states]) => {
        // Set address type.
        this.props.change('type', this.props.type);

        this.setState({
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

  /**
   * Saves the address and invokes callback.
   * @param {Object} props
   * @return {Promise}
   */
  save = props => {
    let { editedAddress } = this.state;

    props = {
      ...props,
      ...editedAddress,
    };

    return this.props
      .addCustomerAddress(this.props.customer, props)
      .then(address => {
        this.props.onSave(address.data);
        this.props.showNotification({
          type: 'success',
          message: 'Address created successfully',
        });
      })
      .catch(err => {
        this.setState({ errors: err.errors });
      });
  };

  /**
   * Update the address in state.
   * @param {Object} address
   */
  onAddressUpdate = address => {
    this.setState({
      editedAddress: address,
    });
  };

  render() {
    const {
      header,
      closeModal,
      saveLabel,
      handleSubmit,
      change,
      backLabel,
      onBackClick,
      hideBack,
    } = this.props;

    const { states, editedAddress } = this.state;

    // Boolean that determines if the address is valid or not.
    const invalid = !isAddressValid(editedAddress);

    return (
      <div id="add-address-modal">
        <ModalHeader title={header} onCloseClick={closeModal} />

        <div class="modal-body AddressSelectionModal">
          {!hideBack && (
            <div
              class="text-primary cursor-pointer AddressSelectionModal__header-action"
              onClick={onBackClick}
            >
              <i class="i i-arrow-back" />
              {backLabel}
            </div>
          )}

          <Alert type="error" message={this.state.errors} />

          <div class="AddressSelectionModal__new-headers">
            <label>Enter a new Address</label>
            {!hideBack && (
              <span class="text-primary cursor-pointer" onClick={onBackClick}>
                Cancel
              </span>
            )}
          </div>

          <form onSubmit={handleSubmit(this.save)}>
            <AddressEntry
              onChange={this.onAddressUpdate}
              states={states}
              hideCountry={true}
              address={editedAddress}
              showDisabledCountry={true}
            />

            <div class="row">
              <div class="col-md-12">
                <div class="Modal__actions">
                  <AsyncButton
                    type="submit"
                    class="btn btn-primary btn-block"
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
