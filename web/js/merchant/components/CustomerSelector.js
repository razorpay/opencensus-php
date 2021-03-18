import React, { Component } from 'react';
import { connect } from 'react-redux';
import CustomerCreation from 'merchant/views/Customers/New';
import { fetchCustomersForAutocomplete } from 'merchant/reducers/customers';
import * as ModalActions from 'merchant_common/reducers/modals';
import { TypeAhead } from 'react-power-select';
import QuickAdd from 'common/ui/Select/QuickAdd';

@connect(
  (state) => {
    return {
      customers: state.customers,
    };
  },
  { fetchCustomersForAutocomplete, ...ModalActions },
)
export default class CustomerSelector extends Component {
  state = { customer: this.props.value || {} };

  get isControlled() {
    return this.props.value && this.props.value.id;
  }

  componentDidMount() {
    this.props.fetchCustomersForAutocomplete();
  }

  openCreateCustomerModal = ({ searchTerm = '' }) => {
    this.props.openModal({
      size: 'small',
      component: (
        <CustomerCreation
          saveLabel="Create and add this customer"
          onSave={this.props.selectCustomerAndCloseModal}
          customer={{
            name: searchTerm,
          }}
        />
      ),
    });
  };

  selectCustomerAndCloseModal = (customer) => {
    this.handleSelectCustomer({ option: customer });

    this.props.closeModal();
  };

  handleSelectCustomer = ({ option }) => {
    if (!this.isControlled) {
      this.setState({
        customer: option,
      });
    }

    this.props.onChange && this.props.onChange(option);
  };

  render() {
    const { customers } = this.props;
    return (
      <TypeAhead
        showClear
        options={customers.items}
        class="ps-in-modal"
        searchIndices={['id', 'name', 'email', 'contact']}
        placeholder={`${customers.loading ? 'Loading...' : 'Select or Add a New Customer'}`}
        disabled={customers.loading || this.props.disabled}
        selected={this.state.customer}
        selectedOptionLabelPath="selectedDisplayName"
        optionComponent={CustomCustomerOption}
        afterOptionsComponent={(extraProps) => (
          <QuickAdd {...extraProps} onClick={this.openCreateCustomerModal} />
        )}
        {...this.props}
        onChange={this.handleSelectCustomer}
      />
    );
  }
}

const CustomCustomerOption = ({ option }) => {
  return (
    <div class="custom-powerselect-options">
      {option.name && <b>{option.name} : </b>}
      {option.email || option.contact}
    </div>
  );
};
