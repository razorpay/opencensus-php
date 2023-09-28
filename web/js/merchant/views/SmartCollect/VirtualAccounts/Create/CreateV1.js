import { Component } from 'react';
import { withRouter } from 'common/deprecated/withRouter';
import { connect } from 'react-redux';
import Banner from 'common/ui/Banner';
import { TypeAhead } from 'react-power-select';

import Input from 'common/new-ui/Input';
import { Modal, ModalContent } from 'common/new-ui/Modal';
import Form from 'common/new-ui/Form';
import Button from 'common/new-ui/Button';
import QuickAdd from 'common/ui/Select/QuickAdd';

import { getKeysSeparatedByPipe, classList } from 'common/utils/rzp-utils';
import {
  validateAlphanumericWithMaxLength,
  validateAlphanumericWithStrictLength,
} from 'common/utils/validators';
import * as ModalActions from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

import { luminateRow } from 'merchant/reducers/app';
import { fetchCustomersForAutocomplete } from 'merchant/reducers/customers';
import {
  saveVirtualAccount,
  fetchConfigForVirtualAccount,
} from 'merchant/reducers/virtualaccounts';

import CustomerCreation from 'merchant/views/Customers/New';

import AccountDetailsSummary from 'merchant/views/SmartCollect/VirtualAccounts/components/Modals/AccountDetailsSummary';

import {
  DESCRIPTOR_LENGTH_BANK_ACCOUNT,
  DESCRIPTOR_LENGTH_VPA,
  getStyle_DescriptorInput_BankAccount,
  getStyle_AddOnBefore_BankAccount,
  getStyle_DescriptorInput_VPA,
  getStyle_AddOnBefore_VPA,
  getStyle_AddOnAfter_VPA,
} from 'merchant/views/SmartCollect/VirtualAccounts/helpers';

const CustomCustomerOption = ({ option }) => {
  return (
    <div class="custom-powerselect-options">
      {option.name && <b>{option.name} : </b>}
      {option.email || option.contact}
    </div>
  );
};

@connect(
  (state) => {
    const customers = state.customers.items;

    return {
      customers,
      customersLoading: state.customers.loading,
      ...state.config.config,
      user: state.session.user,
      va_config: state.virtualaccounts.va_config,
      isTestMode: state.session.mode === 'test',
    };
  },
  {
    luminateRow,
    showNotification,
    saveVirtualAccount,
    fetchCustomersForAutocomplete,
    fetchConfigForVirtualAccount,
    ...ModalActions,
  },
)
class CreateVirtualAccount extends Component {
  state = {
    notes: {},
    close_by: null,
    _internals: {
      hasBankAccount: !this.props.user.isVACreationBankAccountDisabled,
      hasVPA: !!(!this.props.isTestMode || this.props.user.isVACreationBankAccountDisabled),
    },
  };

  componentDidMount() {
    this.props.closeModal(); // Close if previous AccountDetailsSummary modal is opened

    // Make call only when va_config is not available in store, or call failed last time when CreateVirtualAccount modal was opened
    if (!this.props.va_config || !Object.keys(this.props.va_config).length) {
      this.props.fetchConfigForVirtualAccount();
    }

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

  onCopyAccountDetailsSummary = (virtualaccount) => {
    window.rzpAnalytics &&
      window.rzpAnalytics({
        eventCategory: 'Dashboard - Smart Collect',
        eventAction: 'Copy To Clipboard',
        eventLabel: `virtual_account_id${virtualaccount.id}`,
      });
  };

  handleSubmit = (formData) => {
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
      reqPayload.receivers.vpa = descriptorVPA ? { descriptor: descriptorVPA } : {};
    }

    return this.props
      .saveVirtualAccount(reqPayload)
      .then((virtualAccount) => {
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
          const redirectUrl = `/virtualaccounts/${entityId}`;
          this.props.history.push(redirectUrl);
        }

        // On success, Show account details summary
        this.props.openModal({
          size: 'small',
          className: 'VirtualAccountSummary',
          component: (
            <AccountDetailsSummary
              modalTitle="Customer Identifier Created"
              closeModal={this.props.closeModal}
              virtualAccount={virtualAccount}
              onCopy={this.onCopyAccountDetailsSummary}
            />
          ),
        });

        /*global props */
        this.props.onCreateVA && this.props.onCreateVA(props);
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors,
        });
      });
  };

  selectCustomerAndCloseModal = (customer) => {
    this.setState({
      customer,
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

  handleNotesChange = (notes) => {
    this.setState({ notes });
  };

  handleSelectCustomer = ({ option }) => {
    // For setting in redux-form
    this.setState({
      customer: option, // For display purpose only in TypeAhead
    });
  };

  updateDate = (newDate) => {
    this.setState({ close_by: newDate });
  };

  setRefForm = (el) => (this.formEl = el);

  render() {
    const { customers = [], customersLoading, onClose, user, va_config, isTestMode } = this.props;

    const IS_MODAL_VIEW = !!onClose;

    const { _internals } = this.state;
    const disableSubmit = customersLoading || (!_internals.hasVPA && !_internals.hasBankAccount);

    let descriptorLimit_BankAccount, descriptorLimit_VPA;

    if (va_config && Object.keys(va_config).length) {
      if (va_config.hasOwnProperty('bank_account') && va_config.bank_account.isDescriptorEnabled) {
        const bankAccountHandle = va_config.bank_account.prefix;

        descriptorLimit_BankAccount = DESCRIPTOR_LENGTH_BANK_ACCOUNT - bankAccountHandle.length;
      }

      if (
        va_config.hasOwnProperty('vpa') &&
        va_config.vpa.isDescriptorEnabled &&
        va_config.vpa.prefix
      ) {
        const vpaHandle = va_config.vpa.prefix.split('.')[1];

        descriptorLimit_VPA = DESCRIPTOR_LENGTH_VPA - vpaHandle.length;
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
            <div class="form-title">Create Customer Identifier</div>

            {user.isVACreationBankAccountDisabled && (
              <Banner>
                Customer Identifier transfer is temporarily unavailable. Use UPI transfer option to
                create Virtual UPI ID to accept payments.{' '}
                <a
                  class="highlight"
                  target="_blank"
                  href="https://lp.razorpay.com/unregistered-businesses-faqs-0"
                  rel="noreferrer noopener"
                >
                  Know more
                  <i class="i i-external-link" style={{ marginLeft: '5px' }} />
                </a>
              </Banner>
            )}

            <div class="form-group">
              <Input.Group class="Input--vTop" label="Accept Payment Via">
                <div>
                  <Input.Check
                    _name="hasBankAccount"
                    fieldLabel="Bank Transfer ( NEFT, RTGS, IMPS )"
                    defaultValue={_internals.hasBankAccount}
                    disabled={isTestMode || user.isVACreationBankAccountDisabled}
                    onChange={(e) =>
                      this.setState((previousState) => ({
                        ...previousState,
                        _internals: {
                          ...previousState._internals,
                          hasBankAccount: e.target.checked,
                        },
                      }))
                    }
                  />
                  {!!descriptorLimit_BankAccount && (
                    <Input
                      name="descriptorBankAccount"
                      label={() => (
                        <span style={{ fontWeight: 'normal' }}>Customer Identifier Descriptor</span>
                      )}
                      size="vpa_custom"
                      validator={(val) => {
                        if (!validateAlphanumericWithMaxLength(val, descriptorLimit_BankAccount)) {
                          return `Enter only Alphanumeric, upto ${descriptorLimit_BankAccount} characters`;
                        } else {
                          return null;
                        }
                      }}
                      onChange={(e) => {
                        const val = e.target.value;

                        if (validateAlphanumericWithMaxLength(val, descriptorLimit_BankAccount)) {
                          e.target.value = val.toUpperCase();
                        }
                      }}
                      description={
                        _internals.hasBankAccount
                          ? 'If left blank, a customer identifier number will be auto generated'
                          : null
                      }
                      disabled={!_internals.hasBankAccount}
                      style={getStyle_DescriptorInput_BankAccount(va_config)}
                      addonBefore={
                        <span style={getStyle_AddOnBefore_BankAccount(va_config)}>
                          {va_config.bank_account.prefix}
                        </span>
                      }
                    />
                  )}
                </div>

                {!isTestMode && (
                  <>
                    {!!descriptorLimit_BankAccount && <br />}

                    <div>
                      <Input.Check
                        _name="hasVPA"
                        fieldLabel="UPI Transfer"
                        defaultValue={_internals.hasVPA}
                        disabled={user.isVACreationBankAccountDisabled}
                        onChange={(e) =>
                          this.setState((previousState) => ({
                            ...previousState,
                            _internals: {
                              ...previousState._internals,
                              hasVPA: e.target.checked,
                            },
                          }))
                        }
                      />

                      {!!descriptorLimit_VPA && (
                        <Input
                          name="descriptorVPA"
                          label={() => <span style={{ fontWeight: 'normal' }}>UPI ID</span>}
                          size="vpa_custom"
                          validator={(val) => {
                            if (!validateAlphanumericWithStrictLength(val, descriptorLimit_VPA)) {
                              return `Enter only Alphanumeric, ${descriptorLimit_VPA} characters`;
                            } else {
                              return null;
                            }
                          }}
                          description={
                            _internals.hasVPA
                              ? 'If left blank, a UPI ID will be auto generated'
                              : null
                          }
                          disabled={!_internals.hasVPA}
                          style={getStyle_DescriptorInput_VPA(va_config)}
                          addonBefore={
                            <span style={getStyle_AddOnBefore_VPA(va_config)}>
                              {va_config.vpa.prefix}
                            </span>
                          }
                          addonAfter={
                            <span style={getStyle_AddOnAfter_VPA(va_config)}>
                              @{va_config.vpa.handle}
                            </span>
                          }
                        />
                      )}
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
                    placeholder={`${customersLoading ? 'Loading...' : 'Select a customer'}`}
                    showClear={true}
                    selected={this.state.customer}
                    selectedOptionLabelPath="selectedDisplayName"
                    optionComponent={CustomCustomerOption}
                    onChange={this.handleSelectCustomer}
                    afterOptionsComponent={(props) => (
                      <QuickAdd {...props} onClick={this.openCreateCustomerModal} />
                    )}
                  />
                </div>
              </div>

              <Input.TextareaAutoResize
                class="Input--vTop"
                name="description"
                label="Customer Identifier Description"
                description="Description is shown only on the dashboard and not to customers"
              />

              <Input.DateTime
                class="Input--vTop"
                label="Close By"
                checkboxFieldLabel="Disable Auto Close"
                onChange={this.updateDate}
                description="You won’t be able to receive payments after the specified date"
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
              <Button class="btn-outline" type="button" onClick={onClose}>
                Cancel
              </Button>
            )}

            {/* Action Button 2 */}
            <Button.Primary type="submit" disabled={disableSubmit}>
              Create Customer Identifier
            </Button.Primary>
          </footer>
        </Form>
      </div>
    );

    return IS_MODAL_VIEW ? (
      <Modal class={classList('VirtualAccount', content && 'animate-down')} onClose={onClose}>
        <ModalContent>{content}</ModalContent>
      </Modal>
    ) : (
      <div class="StandAloneContainer">{content}</div>
    );
  }
}

export default withRouter(CreateVirtualAccount);
