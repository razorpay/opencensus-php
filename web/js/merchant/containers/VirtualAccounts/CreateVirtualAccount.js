import { Component } from 'react';
import { connect } from 'react-redux';
import { TypeAhead } from 'react-power-select';

import Form from 'component/Form';

import { findBy } from 'rzp/utils/rzp-utils';

import { classList } from 'common/util';
import QuickAddComponent from 'rzp/ui/Select/QuickAdd';

import Input, { Label, Description } from 'component/Input';
import Button, { AsyncBtn } from 'component/Button';

import { closeModal } from 'rzp/modules/modals';
import * as ModalActions from 'rzp/modules/modals';
import { showNotification } from 'rzp/modules/notifications';

import { luminateRow } from 'merchant/modules/app';
import { fetchCustomersForAutocomplete } from 'merchant/modules/customers';
import { saveVirtualAccount } from 'merchant/modules/virtualaccounts';

import CustomerCreation from 'merchant/containers/Customers/New';

import NotesFieldArray from 'merchant/components/NotesFieldArray';
import {
  getVirtualAccountDetails,
  getVirtualAccountDetailsToCopy,
} from 'merchant/components/VirtualAccounts/AccountDetails';
import AccountDetailsSummary from 'merchant/components/VirtualAccounts/AccountDetailsSummary';

const CustomCustomerOption = ({ option }) => {
  return (
    <div class="custom-powerselect-options">
      {option.name && <b>{option.name} : </b>}
      {option.email || option.contact}
    </div>
  );
};

@connect(
  state => {
    const customers = state.customers.items;

    return {
      customers,
      customersLoading: state.customers.loading,
      ...state.config.config,
    };
  },
  {
    closeModal,
    luminateRow,
    showNotification,
    saveVirtualAccount,
    fetchCustomersForAutocomplete,
    ...ModalActions,
  }
)
export default class CreateVirtualAccount extends Component {
  state = { disableSubmit: false };

  componentDidMount() {
    this.props.fetchCustomersForAutocomplete();
    this.props.onMount && this.props.onMount();
  }

  componentWillUnmount() {
    this.props.onUnmount && this.props.onUnmount();
  }

  componentWillReceiveProps(nextProps) {
    // Prepopulate field (Just to display in customer selection. Actual value is props.customer_id, and it's already init through redux-form)
    if (!this.state.customerId && this.props.customer !== nextProps.customer) {
      this.setState({
        customerId: nextProps.customer,
      });
    }
  }

  handleCreate = ({ numeric, descriptor, notes, receivers, ...props }) => {
    let transformedNotes = notes;

    if (transformedNotes && transformedNotes.length > 0) {
      transformedNotes = transformedNotes.reduce((result, current) => {
        result[current.key] = current.value;
        return result;
      }, {});
    }

    return this.props
      .saveVirtualAccount({
        ...props,
        notes: transformedNotes,
        receivers: {
          ...receivers,
          bank_account: descriptor
            ? {
                descriptor,
              }
            : undefined,
        },
      })
      .then(virtualAccount => {
        this.props.luminateRow(virtualAccount.id);

        // On success, Show account details summary
        this.props.openModal({
          size: 'small',
          component: (
            <AccountDetailsSummary
              modalTitle="Virtual Account Created"
              closeModal={this.props.closeModal}
              virtualAccount={virtualAccount}
              onCopy={this.props.onCopy}
            />
          ),
        });

        this.props.onCreateVA && this.props.onCreateVA(props);
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

  onSelectCustomer = ({ option }) => {
    // For setting in redux-form
    if (option) {
      this.props.change('customer_id', option.id);
    } else {
      this.props.untouch('createVirtualAccount', 'customer_id');
    }

    // For display purpose only in TypeAhead
    this.setState({ customerId: option });
  };

  updateDate = newDate => {
    this.setState({ expire_by: newDate });
  };

  toggleDisableState() {
    /*
    * Fields like: 'Time Payable' is required on checkbox. So, if value not selected, html marks it as ':invalid' which is tehnically valid in our case.
    * Hence, relying on is-invalid.
    * */
    const invalidFields = document.querySelectorAll(
      '.PaymentLinks--Create-Form .Input.is-invalid'
    );
    const disableSubmit = invalidFields.length;

    if (this.state.disableSubmit !== disableSubmit) {
      this.setState({ disableSubmit });
    }
  }

  render() {
    const {
      close_by,
      handle = '',
      handleSubmit,
      customers = [],
      descriptor = '',
      customersLoading,
      onClose,
    } = this.props;

    let descriptorLimit;

    if (handle) {
      if (handle.length === 3) {
        descriptorLimit = 10;
      } else if (handle.length === 4) {
        descriptorLimit = 9;
      }
    }

    const dateInMoment = undefined;

    const IS_MODAL_VIEW = this.props.onClose;

    const content = (
      <div class="PaymentLinks--Create Wizard">
        <main class="form-container">
          <main-title class="main-title">Create Virtual Account</main-title>

          <Form
            class="PaymentLinks--Create-Form"
            onChange={this.props.onChange}
            layout="tabular"
          >
            <Input.Group class="Input--vTop" label="Accept Payment Via">
              <br />

              <div>
                <Input.Check
                  _name="hasBankAccount"
                  fieldLabel="Bank Transfer ( NEFT, RTGS, IMPS )"
                />
                <Input label="Account Number" name="bank_account" />
              </div>

              <div>
                <Input.Check _name="hasVPA" fieldLabel="UPI Transfer" />
                <Input label="UPI ID" name="vpa" />
              </div>
            </Input.Group>

            {/*
            <Input.PowerDropdown
              name="customer"
              label="Customer (Optional)"
              defaultValue={this.state.customerId}
              placeholder={`${
                customersLoading ? 'Loading...' : 'Select a customer'
              }`}
              options={this.typeOptions}
              customOptionComponent={CustomCustomerOption}
              customSelectedOptionComponent={CustomCustomerOption}
              onChange={this.onSelectCustomer}
              afterOptionsComponent={select => (
                <QuickAddComponent {...select} onClick={this.quickCreateCustomer} />
              )}
            />
            */}

            <Input.TextareaAutoResize
              class="Input--vTop"
              name="description"
              label="Account Description (Optional)"
              description="Description is shown only on the dashboard and not to customers"
            />

            <Input.DateTime
              class="Input--vTop"
              label="Close By"
              checkboxFieldLabel="Enable Auto Close"
              onChange={this.updateDate}
              description="You won’t be able to recieve payments after the specified date"
              isInline
            />

            <Input.PairList
              class="Input-vTop"
              name="notes"
              label="Internal Notes"
            />
          </Form>
        </main>

        {/* Form Footer */}
        <footer>
          {/* Action Button 1 */}
          {this.props.isModalView && <Button onClick={onClose}>Cancel</Button>}

          {/* Action Button 2 */}
          <AsyncBtn.Primary
            onClick={this.handleCreate}
            pendingState={'Creating...'}
            disabled={this.state.disableSubmit || customersLoading}
          >
            Create Virtual Account
          </AsyncBtn.Primary>
        </footer>
      </div>
    );

    return IS_MODAL_VIEW ? (
      <Modal
        class={classList('PaymentLinks', content && 'animate-down')}
        onClose={onClose}
      >
        <ModalContent>{content}</ModalContent>
      </Modal>
    ) : (
      <div class="StandAloneContainer">{content}</div>
    );
  }
}
