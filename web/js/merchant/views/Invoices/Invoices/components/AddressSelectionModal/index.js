import { Component } from 'react';
import { connect } from 'react-redux';

import { capitalize } from 'common/utils/rzp-utils';
import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';

import List from './Screens/List';
import New from './Screens/New';
import { track } from '../../../ga';

class AddressSelectionModal extends Component {
  static propTypes = {
    /**
     * Customer object.
     */
    customer: PropTypes.object.isRequired,

    /**
     * Array of Addresses (objects).
     */
    addresses: PropTypes.array,

    /**
     * Selected address.
     */
    selected: PropTypes.object,

    /**
     * Callback for when an Address is to be selected.
     */
    onSelect: PropTypes.func,

    /**
     * Callback for when an address is added.
     */
    onSave: PropTypes.func,

    /**
     * Text for back button.
     */
    backLabel: PropTypes.string,

    /**
     * Type of address (billing_address/shipping_address).
     */
    type: PropTypes.string,

    /**
     * Type of address (billing/shipping)
     */
    addressType: PropTypes.string,
  };

  static defaultProps = {
    onSave: () => {},
    onSelect: () => {},
    backLabel: 'View All Addresses',
    type: 'billing_address',
    addressType: 'billing',
  };

  constructor(props) {
    super(props);
    this.state = {
      /**
       * If there are no addresses yet, directly show the add address screen.
       */
      showAddAddress: props.addresses && props.addresses.length === 0,
    };
  }

  componentDidMount() {
    /**
     * This component is not within a `render` method
     * and thus won't be updated if the state/props of the parent container update.
     * Due to this, in order to update things within this component, we will have to rely on
     * updating this component's state and then invoking relevant callback.
     * So, we copy all the props to the state in order to have them accessible.
     */
    this.setState({
      ...this.props,
    });
  }

  /**
   * Method to be invoked when an address is selected.
   * @param {Object} address Address that was selected
   */
  onSelect = (address) => {
    /**
     * 1. Set the selected address.
     * 2. Invoke onSelect callback.
     */
    this.setState({
      selected: address,
    });
    this.props.onSelect(address);
    this.props.closeModal();
  };

  /**
   * Method to show the add form.
   */
  onAddClick = () => {
    const { addressType } = this.props;

    track({
      eventAction: `Click - Add ${capitalize(addressType)} Address`,
    });

    this.setState({
      showAddAddress: true,
    });
  };

  /**
   * Method to go back to address list
   */
  onBackClick = () => {
    this.setState({
      showAddAddress: false,
    });
  };

  /**
   * Callback for when an address is added.
   */
  onSave = (address) => {
    this.props.onSave(address);
  };

  render() {
    const {
      header,
      type,
      backLabel,
      addressType,
      isInttCurrenciesEnabled,
      onBlur,
      onClickClose,
      trackSelectCountry,
      trackAddressSelection,
    } = this.props;

    const { customer, selected, showAddAddress, addresses } = this.state;

    /**
     * The `customer` object has not been set yet, and this is the first paint.
     * Ignore and don't render anything yet.
     */
    if (!customer) {
      return null;
    }

    const addressList = (
      <List
        header={header}
        selected={selected}
        onAddClick={this.onAddClick}
        addresses={addresses}
        onSave={this.onSelect}
      />
    );

    const newScreen = (
      <New
        header={header}
        customer={customer}
        backLabel={backLabel}
        onBackClick={this.onBackClick}
        type={type}
        onSave={this.onSave}
        hideBack={addresses && addresses.length === 0}
        addressType={addressType}
        isInttCurrenciesEnabled={isInttCurrenciesEnabled}
        trackSelectCountry={trackSelectCountry}
        onClickClose={onClickClose}
        onBlur={onBlur}
        trackAddressSelection={trackAddressSelection}
      />
    );

    if (showAddAddress) {
      return newScreen;
    } else {
      return addressList;
    }
  }
}

export default connect(null, {
  ...ModalActions,
  ...NotificationsActions,
})(AddressSelectionModal);
