import { Component } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm, formValueSelector } from 'redux-form';
import { TypeAhead } from 'react-power-select';
import { findBy } from 'rzp/utils/rzp-utils';
import AsyncButton from 'react-async-button';
import ModalHeader from 'rzp/ui/ModalHeader';
import { showNotification } from 'rzp/modules/notifications';
import { closeModal } from 'rzp/modules/modals';
import { luminateRow } from 'merchant/modules/app';
import { saveVirtualAccount } from 'merchant/modules/virtualaccounts';
import { fetchConfig } from 'merchant/modules/config';
import { fetchCustomersForAutocomplete } from 'merchant/modules/customers';
import CustomClipboard from 'rzp/ui/Clipboard/Custom';
import CustomerCreation from 'merchant/containers/Customers/New';
import QuickAddComponent from 'rzp/ui/Select/QuickAdd';
import * as ModalActions from 'rzp/modules/modals';

const VirtualAccountDetails = ({ virtualAccount, onCopy }) => {
  let bankAccount = virtualAccount.receivers[0];
  return (
    <div>
      <p class="text-muted">
        Share the following information with the customer to accept payments
      </p>

      <div class="form-group">
        <div class="text-muted">Account Number</div>
        <div>
          <b>{bankAccount.account_number}</b>
        </div>
      </div>

      <div class="form-group">
        <div class="text-muted">Beneficiary Name</div>
        <div>
          <b>{virtualAccount.name}</b>
        </div>
      </div>

      <div class="form-group">
        <div class="text-muted">IFSC Code</div>
        <div>
          <b>{bankAccount.ifsc}</b>
        </div>
      </div>

      <CustomClipboard
        value={`Account Number: ${bankAccount.account_number}\nBeneficiary Name: ${virtualAccount.name}\nIFSC: ${bankAccount.ifsc}`}
        onCopy={() => {
          onCopy(virtualAccount);
        }}
      >
        <button type="button" class="btn btn-primary btn-block">
          Copy details to Clipboard
        </button>
      </CustomClipboard>
    </div>
  );
};

const selector = formValueSelector('createVirtualAccount');
@connect(
  state => {
    const customers = state.customers.items;
    return {
      descriptor: selector(state, 'descriptor'),
      customers,
      customersLoading: state.customers.loading,
      customer: findBy(customers, 'id', selector(state, 'customer_id')),
      ...state.config.config,
    };
  },
  {
    luminateRow,
    closeModal,
    showNotification,
    saveVirtualAccount,
    fetchConfig,
    fetchCustomersForAutocomplete,
    ...ModalActions,
  }
)
@reduxForm({
  form: 'createVirtualAccount',
})
export default class CreateVirtualAccount extends Component {
  state = {};

  componentWillMount() {
    this.props.fetchConfig();
    this.props.fetchCustomersForAutocomplete();
  }

  componentDidMount() {
    this.props.onMount && this.props.onMount();
  }

  componentWillUnmount() {
    this.props.onUnmount && this.props.onUnmount();
  }

  componentWillReceiveProps(nextProps) {
    // Prepoluate field (Just to display in customer selection. Actual value is props.customer_id, and it's already init through redux-form)
    if (!this.state.customerId && this.props.customer !== nextProps.customer) {
      this.setState({
        customerId: nextProps.customer,
      });
    }
  }

  save = props => {
    return this.props
      .saveVirtualAccount(props)
      .then(virtualAccount => {
        this.props.onCreateVA && this.props.onCreateVA(props);
        this.props.luminateRow(virtualAccount.id);
        this.setState({ virtualAccount });
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors,
        });
      });
  };

  selectCustomerAndCloseModal = customer => {
    this.props.change('customer_id', customer.id);
    this.props.closeModal();
    setTimeout(this.props.showCreateVAModal, 500); // To open create virtual account modal automatically with pre-selected customer name
  };

  quickCreateCustomer = ({ searchTerm = '' }) => {
    this.props.openModal({
      size: 'small',
      component: (
        <CustomerCreation
          saveLabel="Create and add this customer"
          onSave={this.selectCustomerAndCloseModal}
          customer={{
            name: searchTerm,
          }}
        />
      ),
    });
  };

  handleChange = () => {
    setTimeout(() =>
      document
        .getElementsByClassName('virtual-account-powerselect__Menu')[0]
        .parentNode.classList.add('super-impose')
    );
  };

  handleSelect = ({ option }) => {
    // For setting in redux-form
    if (option) {
      this.props.change('customer_id', option.id);
    } else {
      this.props.untouch('createVirtualAccount', 'customer_id');
    }

    // For display purpose only in TypeAhead
    this.setState({ customerId: option });
  };

  render() {
    const {
      handleSubmit,
      untouch,
      handle = '',
      descriptor = '',
      customersLoading,
      customers = [],
      onCopy = () => {},
    } = this.props;
    const { virtualAccount } = this.state;

    let descriptorLimit;

    if (handle) {
      if (handle.length === 3) {
        descriptorLimit = 10;
      } else if (handle.length === 4) {
        descriptorLimit = 9;
      }
    }

    return (
      <div>
        <ModalHeader
          title={
            virtualAccount
              ? 'Virtual Account Created'
              : 'Create Virtual Account'
          }
          onCloseClick={this.props.closeModal}
        />

        <div class="modal-body">
          {virtualAccount ? (
            <VirtualAccountDetails
              virtualAccount={virtualAccount}
              onCopy={onCopy}
            />
          ) : (
            <form onSubmit={handleSubmit(this.save)}>
              <div class="form-group">
                <label>Customer (Optional)</label>
                <TypeAhead
                  options={customers}
                  disabled={customersLoading}
                  class="virtual-account-powerselect"
                  searchIndices={['id', 'name', 'email', 'contact']}
                  placeholder={`${customersLoading
                    ? 'Loading...'
                    : 'Select a customer'}`}
                  showClear={true}
                  selected={this.state.customerId}
                  selectedOptionLabelPath="selectedDisplayName"
                  optionComponent={({ option }) => {
                    return (
                      <div class="custom-powerselect-options">
                        {option.name && <b>{option.name} : </b>}
                        {option.email || option.contact}
                      </div>
                    );
                  }}
                  onClick={this.handleChange}
                  onChange={this.handleSelect}
                  afterOptionsComponent={select => (
                    <QuickAddComponent
                      {...select}
                      onClick={this.quickCreateCustomer}
                    />
                  )}
                />
              </div>

              <div class="form-group">
                <label>Account Description (Optional)</label>
                <Field
                  name="description"
                  class="form-control"
                  component="input"
                  required={true}
                />
                <small class="help-block">
                  Account description is only displayed on the dashboard and is
                  not shared with the customer.
                </small>
              </div>

              {handle ? (
                <div class="form-group">
                  <label>Descriptor</label>
                  <Field
                    name="descriptor"
                    component="input"
                    class="form-control"
                    placeholder={`Accepts alphanumberic, upto ${descriptorLimit} chars`}
                    normalize={value => value.toUpperCase()}
                    onChange={event => {
                      let value = event.target.value;
                      let regex = new RegExp(
                        `^[a-z0-9]{0,${descriptorLimit}}$`,
                        'i'
                      );

                      if (regex.test(value)) {
                        this.props.change('descriptor', value);
                      } else {
                        event.preventDefault();
                      }
                    }}
                  />
                  <small class="help-block">
                    Descriptor will be a part of the account number generated.
                  </small>
                </div>
              ) : null}

              <div class="Modal__actions clearfix">
                <AsyncButton
                  className="btn btn-primary btn-block"
                  text="Create"
                  pendingText="Creating..."
                  onClick={handleSubmit(this.save)}
                />
              </div>
            </form>
          )}
        </div>
      </div>
    );
  }
}
