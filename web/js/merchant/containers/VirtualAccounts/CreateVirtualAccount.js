import { Component } from 'react';
import { withRouter } from 'react-router-dom';
import { connect } from 'react-redux';
import { TypeAhead } from 'react-power-select';

import Input, { Label, Description } from 'common/new-ui/Input';
import { Modal, ModalContent } from 'common/new-ui/Modal';
import Form from 'common/new-ui/Form';
import Button from 'common/new-ui/Button';
import QuickAdd from 'common/ui/Select/QuickAdd';

import {
  findBy,
  getKeysSeparatedByPipe,
  classList,
} from 'common/utils/rzp-utils';
import { validateVABankAccount } from 'common/utils/validators';
import { closeModal } from 'merchant_common/reducers/modals';
import * as ModalActions from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

import { luminateRow } from 'merchant/reducers/app';
import { fetchCustomersForAutocomplete } from 'merchant/reducers/customers';
import { saveVirtualAccount } from 'merchant/reducers/virtualaccounts';

import CustomerCreation from 'merchant/views/Customers/New';

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

@withRouter
@connect(
  state => {
    const customers = state.customers.items;

    return {
      customers,
      customersLoading: state.customers.loading,
      ...state.config.config,
      user: state.session.user,
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
      hasVPA: this.props.user.isVPAFeatureEnabled ? true : false,
    },
  };

  componentDidMount() {
    this.props.closeModal(); // Close if previous AccountDetailsSummary modal is opened

    this.props.fetchCustomersForAutocomplete();

    window.rzpAnalytics &&
      window.rzpAnalytics({
        eventCategory: 'Dashboard - Smart Collect',
        eventAction: 'Open Form - Create Virtual Account',
      });
  }

  componentWillUnmount() {
    window.rzpAnalytics &&
      window.rzpAnalytics({
        eventCategory: 'Dashboard - Smart Collect',
        eventAction: 'Close Form - Create Virtual Account',
      });
  }

  onCopyAccountDetailsSummary = virtualaccount => {
    window.rzpAnalytics &&
      window.rzpAnalytics({
        eventCategory: 'Dashboard - Smart Collect',
        eventAction: 'Copy To Clipboard',
        eventLabel: `virtual_account_id${virtualaccount.id}`,
      });
  };

  handleSubmit = formData => {
    const { descriptorVPA, descriptorBankAccount, description } = formData;
    const { notes, close_by, _internals, customer } = this.state;

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
      close_by: close_by ? close_by.unix() : undefined,
      description,
    };

    if (customer) {
      reqPayload.customer_id = customer.id;
    }

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
        window.rzpAnalytics &&
          window.rzpAnalytics({
            eventCategory: 'Dashboard - Smart Collect',
            eventAction: 'Submit Form - Create Virtual Account',
            eventLabel: getKeysSeparatedByPipe(reqPayload),
          });

        const entityId = virtualAccount.id;
        const IS_MODAL_VIEW = !!this.props.onClose;

        this.props.luminateRow(entityId);

        if (IS_MODAL_VIEW) {
          setTimeout(this.props.onClose, 50);
        } else {
          const redirectUrl = '/virtualaccounts/' + entityId;
          this.props.history.push(redirectUrl);
        }

        // On success, Show account details summary
        this.props.openModal({
          size: 'small',
          className: 'VirtualAccountSummary',
          component: (
            <AccountDetailsSummary
              modalTitle="Virtual Account Created"
              closeModal={this.props.closeModal}
              virtualAccount={virtualAccount}
              onCopy={this.onCopyAccountDetailsSummary}
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
      user,
    } = this.props;

    const IS_MODAL_VIEW = !!onClose;

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
      <div class="VirtualAccount--Create Wizard">
        <Form
          onChange={this.props.onChange}
          onSubmit={this.handleSubmit}
          layout="tabular"
          ref={this.setRefForm}
        >
          <main>
            <div class="form-title">Create Virtual Account</div>
            <div class="form-group">
              <Input.Group class="Input--vTop" label="Accept Payment Via">
                <div>
                  <Input.Check
                    _name="hasBankAccount"
                    fieldLabel="Bank Transfer ( NEFT, RTGS, IMPS )"
                    defaultValue={_internals.hasBankAccount}
                    disabled={!user.isVPAFeatureEnabled}
                    onChange={e =>
                      this.setState({
                        _internals: {
                          ...this.state._internals,
                          hasBankAccount: e.target.checked,
                        },
                      })
                    }
                  />
                  {!!handle && (
                    <Input
                      name="descriptorBankAccount"
                      label={() => (
                        <span style={{ fontWeight: 'normal' }}>
                          Account Descriptor
                        </span>
                      )}
                      size="half_big"
                      placeholder={`Alphanumberic, upto ${descriptorLimit} characters`}
                      validator={val => {
                        if (!validateVABankAccount(val, descriptorLimit)) {
                          return `Enter only Alphanumberic, upto ${descriptorLimit} characters`;
                        }
                      }}
                      onChange={e => {
                        let val = e.target.value;

                        if (validateVABankAccount(val, descriptorLimit)) {
                          e.target.value = val.toUpperCase();
                        }
                      }}
                      description={
                        _internals.hasBankAccount
                          ? 'If left blank, an account number will be auto generated'
                          : null
                      }
                      disabled={!_internals.hasBankAccount}
                    />
                  )}
                </div>

                {!!user.isVPAFeatureEnabled && (
                  <>
                    {!!handle && <br />}

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
                  </>
                )}
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
            </div>
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
        </Form>
      </div>
    );

    return IS_MODAL_VIEW ? (
      <Modal
        class={classList('VirtualAccount', content && 'animate-down')}
        onClose={onClose}
      >
        <ModalContent>{content}</ModalContent>
      </Modal>
    ) : (
      <div class="StandAloneContainer">{content}</div>
    );
  }
}
