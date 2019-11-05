import { Component } from 'react';
import { connect } from 'react-redux';
import { TypeAhead } from 'react-power-select';

import { Modal, ModalContent } from 'component/Modal';
import Form from 'component/Form';

import { findBy } from 'rzp/utils/rzp-utils';

import { classList } from 'common/util';
import QuickAdd from 'rzp/ui/Select/QuickAdd';

import Input, { Label, Description } from 'component/Input';
import Button, { AsyncBtn } from 'component/Button';

import { closeModal } from 'rzp/modules/modals';
import * as ModalActions from 'rzp/modules/modals';
import { showNotification } from 'rzp/modules/notifications';

import { luminateRow } from 'merchant/modules/app';
import { fetchCustomersForAutocomplete } from 'merchant/modules/customers';
import { saveVirtualAccount } from 'merchant/modules/virtualaccounts';

import CustomerCreation from 'merchant/containers/Customers/New';

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
  state = {
    notes: {},
    close_by: null,
    _internals: {
      hasBankAccount: true,
      hasVPA: true,
    },
  };

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

  handleSubmit = formData => {
    const { descriptorVPA, descriptorBankAccount } = formData;
    const { notes, close_by, _internals } = this.state;

    let transformedNotes = notes;

    if (transformedNotes && transformedNotes.length > 0) {
      transformedNotes = transformedNotes.reduce((result, current) => {
        result[current.key] = current.value;
        return result;
      }, {});
    }

    const reqPayload = {
      receivers: {
        notes: [],
        types: [],
      },
      notes: transformedNotes,
      close_by: close_by || undefined,
    };

    if (_internals.hasBankAccount) {
      reqPayload.receivers.types.push('bank_account');
      reqPayload.receivers.bank_account = descriptorBankAccount
        ? { descriptor: descriptorBankAccount }
        : {};
    }

    if (_internals.hasVPA) {
      reqPayload.receivers.types.push('vpa');
      reqPayload.receivers.vpa = descriptorVPA
        ? { descriptor: descriptorVPA }
        : {};
    }

    return this.props
      .saveVirtualAccount(reqPayload)
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
    this.setState({
      customer: customer,
    });

    this.props.closeModal();
  };

  openCreateCustomerModal = ({ searchTerm = '' }) => {
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

  handleNotesChange = notes => {
    this.setState({ notes });
  };

  handleSelectCustomer = ({ option }) => {
    // For setting in redux-form
    this.setState({
      customer_id: option ? option.id : null,
      customer: option, // For display purpose only in TypeAhead
    });
  };

  updateDate = newDate => {
    this.setState({ close_by: newDate });
  };

  setRefForm = el => (this.formEl = el);

  render() {
    const {
      handle = '',
      customers = [],
      customersLoading,
      onClose,
    } = this.props;

    const IS_MODAL_VIEW = onClose;

    const { _internals } = this.state;
    const disableSubmit =
      customersLoading || (!_internals.hasVPA && !_internals.hasBankAccount);

    let descriptorLimit;

    if (handle) {
      if (handle.length === 3) {
        descriptorLimit = 10;
      } else if (handle.length === 4) {
        descriptorLimit = 9;
      }
    }

    const content = (
      <div class="PaymentLinks--Create Wizard">
        <main class="form-container">
          <main-title class="main-title">Create Virtual Account</main-title>

          <Form
            class="PaymentLinks--Create-Form"
            onChange={this.props.onChange}
            onSubmit={this.handleSubmit}
            layout="tabular"
            ref={this.setRefForm}
          >
            <Input.Group class="Input--vTop" label="Accept Payment Via">
              <div>
                <Input.Check
                  _name="hasBankAccount"
                  fieldLabel="Bank Transfer ( NEFT, RTGS, IMPS )"
                  defaultValue={_internals.hasBankAccount}
                  onChange={e =>
                    this.setState({
                      _internals: {
                        ...this.state._internals,
                        hasBankAccount: e.target.checked,
                      },
                    })
                  }
                />
                <Input
                  name="descriptorBankAccount"
                  label={() => (
                    <span style={{ fontWeight: 'normal' }}>Account Number</span>
                  )}
                  size="half_big"
                  description={
                    _internals.hasBankAccount
                      ? 'If left blank, an account number will be auto generated'
                      : null
                  }
                  disabled={!_internals.hasBankAccount}
                />
              </div>

              <br />

              <div>
                <Input.Check
                  _name="hasVPA"
                  fieldLabel="UPI Transfer"
                  defaultValue={_internals.hasVPA}
                  onChange={e =>
                    this.setState({
                      _internals: {
                        ...this.state._internals,
                        hasVPA: e.target.checked,
                      },
                    })
                  }
                />
                <Input
                  name="descriptorVPA"
                  label={() => (
                    <span style={{ fontWeight: 'normal' }}>UPI ID</span>
                  )}
                  size="half_big"
                  description={
                    _internals.hasVPA
                      ? 'If left blank, a UPI ID will be auto generated'
                      : null
                  }
                  disabled={!_internals.hasVPA}
                />
              </div>
            </Input.Group>

            <div class="Input">
              <div class="Input-label">Customer</div>

              <div class="Input-content">
                <TypeAhead
                  options={customers}
                  disabled={customersLoading}
                  class="ps-in-modal"
                  searchIndices={['id', 'name', 'email', 'contact']}
                  placeholder={`${
                    customersLoading ? 'Loading...' : 'Select a customer'
                  }`}
                  showClear={true}
                  selected={this.state.customer}
                  selectedOptionLabelPath="selectedDisplayName"
                  optionComponent={CustomCustomerOption}
                  onChange={this.handleSelectCustomer}
                  afterOptionsComponent={props => (
                    <QuickAdd
                      {...props}
                      onClick={this.openCreateCustomerModal}
                    />
                  )}
                />
              </div>
            </div>

            <Input.TextareaAutoResize
              class="Input--vTop"
              name="description"
              label="Account Description"
              description="Description is shown only on the dashboard and not to customers"
            />

            <Input.DateTime
              class="Input--vTop"
              label="Close By"
              checkboxFieldLabel="Disable Auto Close"
              onChange={this.updateDate}
              description="You won’t be able to recieve payments after the specified date"
              isInline
            />

            <Input.PairList
              class="Input--vTop"
              name="notes"
              label="Internal Notes"
              onChange={this.handleNotesChange}
            />
          </Form>
        </main>

        {/* Form Footer */}
        <footer>
          {/* Action Button 1 */}
          {IS_MODAL_VIEW && (
            <Button type="button" onClick={onClose}>
              Cancel
            </Button>
          )}

          {/* Action Button 2 */}
          <Button.Primary type="submit" disabled={disableSubmit}>
            Create Virtual Account
          </Button.Primary>
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
