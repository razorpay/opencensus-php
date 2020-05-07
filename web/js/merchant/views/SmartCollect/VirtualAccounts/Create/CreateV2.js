import { withRouter } from 'react-router-dom';
import { connect } from 'react-redux';

import { Modal, ModalContent } from 'common/new-ui/Modal';
import { TypeAhead } from 'react-power-select';
import SelectBox from 'common/new-ui/SelectBox';
import Button from 'common/new-ui/Button';
import QuickAdd from 'common/ui/Select/QuickAdd';
import Form from 'common/new-ui/Form';
import Input from 'common/new-ui/Input';
import Spinner from 'common/ui/Spinner';

import { classList } from 'common/utils/rzp-utils';
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

import AccountDetailsSummary from '../components/Modals/AccountDetailsSummary';
import VPAPrefixModal from './VPAPrefixModal';

import {
  DESCRIPTOR_LENGTH_BANK_ACCOUNT,
  DESCRIPTOR_LENGTH_VPA,
  getStyle_DescriptorInput_BankAccount,
  getStyle_AddOnBefore_BankAccount,
  getStyle_DescriptorInput_VPA,
  getStyle_AddOnBefore_VPA,
  getStyle_AddOnAfter_VPA,
} from 'merchant/views/SmartCollect/VirtualAccounts/helpers';

@withRouter
@connect(
  state => {
    const customers = state.customers.items;

    return {
      customers,
      ...state.config.config,
      user: state.session.user,
      isTestMode: state.session.mode === 'test',
      va_config: state.virtualaccounts.va_config || {},
    };
  },
  {
    luminateRow,
    showNotification,
    saveVirtualAccount,
    ...ModalActions,
    fetchConfigForVirtualAccount,
    fetchCustomersForAutocomplete,
  }
)
export default class CreateVirtualAccount extends React.Component {
  constructor(props) {
    super();

    this.state = {
      notes: {},
      close_by: null,
      _internals: {
        hasBankAccount: !props.user.isVACreationBankAccountDisabled,
        hasVPA: !!props.user.isVACreationBankAccountDisabled,
      },
      isLoading: true,
      isUpdating: false,
      showAdditionalOptions: false,
      descriptors: {
        vpa: '',
        bank_account: '',
      },
    };
  }

  componentDidMount() {
    this.fetchDataForVA();
  }

  fetchDataForVA = () => {
    const promiseList = [];

    const isVAConfigAvl =
      this.props.va_config.bank_account || this.props.va_config.vpa;

    if (!isVAConfigAvl) {
      promiseList.push(this.props.fetchConfigForVirtualAccount());
    }

    promiseList.push(this.props.fetchCustomersForAutocomplete());

    Promise.all(promiseList)
      .then(() => {
        this.setState({
          isLoading: false,
        });
      })
      .catch(() => {
        this.setState({
          isLoading: false,
        });
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

    this.setState({
      isUpdating: true,
    });

    return this.props
      .saveVirtualAccount(reqPayload)
      .then(virtualAccount => {
        this.setState({
          isUpdating: false,
        });

        this.props.closeModal();

        const entityId = virtualAccount.id;

        this.props.luminateRow(entityId);

        const redirectUrl = '/virtualaccounts/' + entityId;
        this.props.history.push(redirectUrl);

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
      })
      .catch(({ errors }) => {
        this.setState({
          isUpdating: false,
        });

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
    this.setState({
      customer_id: option ? option.id : null,
      customer: option,
    });
  };

  updateDate = newDate => {
    this.setState({ close_by: newDate });
  };

  handlePaymentMethod = name => () => {
    this.setState({
      _internals: {
        ...this.state._internals,
        [name]: !this.state._internals[name],
      },
    });
  };

  handleDescriptor = name => event => {
    this.setState({
      descriptors: {
        ...this.state.descriptors,
        [name]: event.target.value,
      },
    });
  };

  handleAdditionalOptions = () => {
    this.setState(
      {
        showAdditionalOptions: !this.state.showAdditionalOptions,
      },
      () => {
        setTimeout(() => {
          const additionalOptionsElement = document.querySelector(
            '.AdditionalOptions'
          );

          if (additionalOptionsElement) {
            additionalOptionsElement.scrollIntoView({
              behavior: 'smooth',
              block: 'center',
            });
          }
        }, 100);
      }
    );
  };

  handleAddNewNote = freshPairs => {
    setTimeout(() => {
      const newNoteElement = document.getElementsByName(
        `notes[${freshPairs.length - 1}][value]`
      )[0];

      if (newNoteElement) {
        newNoteElement.scrollIntoView({
          behavior: 'smooth',
          block: 'center',
        });
      }
    }, 100);
  };

  openVPAPrefixModal = () => {
    this.props.openModal({
      size: 'medium',
      className: 'VPAPrefixModal',
      component: <VPAPrefixModal />,
    });
  };

  render() {
    let { customers = [], onClose, va_config, user, isTestMode } = this.props;

    const IS_MODAL_VIEW = !!onClose;

    const {
      _internals,
      showAdditionalOptions,
      isLoading,
      isUpdating,
      descriptors,
    } = this.state;

    const disableSubmit =
      isLoading ||
      (!_internals.hasVPA && !_internals.hasBankAccount) ||
      isUpdating;

    const { bank_account: bankAccountConfig, vpa: vpaConfig } = va_config;

    let descriptorLimit_BankAccount, descriptorLimit_VPA;

    if (bankAccountConfig && bankAccountConfig.isDescriptorEnabled) {
      descriptorLimit_BankAccount =
        DESCRIPTOR_LENGTH_BANK_ACCOUNT - bankAccountConfig.prefix.length;
    }

    if (
      vpaConfig &&
      vpaConfig.isDescriptorEnabled &&
      vpaConfig.merchant_prefix
    ) {
      descriptorLimit_VPA =
        DESCRIPTOR_LENGTH_VPA - vpaConfig.merchant_prefix.length;
    }

    const showBankAccountDescriptor =
      !!descriptorLimit_BankAccount && _internals.hasBankAccount;
    let showVPADescriptor = !!descriptorLimit_VPA && _internals.hasVPA;
    let showVPAPrefix = true;

    if (
      user.isUnregisteredBusiness &&
      vpaConfig &&
      vpaConfig.merchant_prefix === 'payto00000'
    ) {
      showVPADescriptor = false;
      showVPAPrefix = false;
    }

    const content = (
      <div class="VirtualAccount--CreateV2 Wizard">
        <Form
          onChange={this.props.onChange}
          onSubmit={this.handleSubmit}
          ref={this.setRefForm}
        >
          <main>
            <div class="form-title">Create Virtual Account</div>

            {isLoading ? (
              <div class="page-spinner-container">
                <Spinner />
              </div>
            ) : (
              <>
                <label class="payment-method-label">
                  Methods to accept payments in this account
                </label>

                <SelectBox
                  name="hasBankAccount"
                  class="SelectBox--Outline"
                  onClick={this.handlePaymentMethod('hasBankAccount')}
                  defaultChecked={_internals.hasBankAccount}
                  label={
                    <div>
                      <img src="/dist/css/assets/bank.svg" /> Fund Transfers
                      (NEFT, RTGS, IMPS)
                    </div>
                  }
                  description="Get bank account details to accept fund transfers."
                >
                  {showBankAccountDescriptor && (
                    <Input
                      name="descriptorBankAccount"
                      size="vpa_custom"
                      description={
                        <>
                          <div class="remaining-count">
                            {descriptors.bank_account.length} /{' '}
                            {descriptorLimit_BankAccount}
                          </div>
                          <br />
                          If left blank, an account number will be auto
                          generated
                        </>
                      }
                      style={getStyle_DescriptorInput_BankAccount(va_config)}
                      onChange={this.handleDescriptor('bank_account')}
                      validator={validateCustomBankAccountNumber(
                        descriptorLimit_BankAccount
                      )}
                      addonBefore={
                        <span
                          style={getStyle_AddOnBefore_BankAccount(va_config)}
                        >
                          {bankAccountConfig.prefix}
                        </span>
                      }
                    />
                  )}
                </SelectBox>

                {!isTestMode && (
                  <SelectBox
                    name="hasVPA"
                    class="SelectBox--Outline"
                    onClick={this.handlePaymentMethod('hasVPA')}
                    defaultChecked={_internals.hasVPA}
                    label={
                      <div>
                        <img src="/dist/css/assets/upi.svg" /> UPI Transfers
                        (GPay, PhonePe, etc.)
                      </div>
                    }
                    description={
                      showVPAPrefix && (
                        <>
                          To update{' '}
                          <strong>"{vpaConfig.merchant_prefix}"</strong> prefix{' '}
                          <a onClick={this.openVPAPrefixModal}>click here</a>
                        </>
                      )
                    }
                  >
                    {showVPADescriptor && (
                      <Input
                        name="descriptorVPA"
                        size="vpa_custom"
                        disabled={false}
                        validator={validateCustomVPA(descriptorLimit_VPA)}
                        description={
                          <>
                            <div class="remaining-count">
                              {descriptors.vpa.length} / {descriptorLimit_VPA}
                            </div>
                            <br />
                            UPI ID is auto generated if details are left blank
                          </>
                        }
                        style={getStyle_DescriptorInput_VPA(va_config)}
                        onChange={this.handleDescriptor('vpa')}
                        addonBefore={
                          <span style={getStyle_AddOnBefore_VPA(va_config)}>
                            {vpaConfig.prefix}
                          </span>
                        }
                        addonAfter={
                          <span style={getStyle_AddOnAfter_VPA(va_config)}>
                            @{vpaConfig.handle}
                          </span>
                        }
                      />
                    )}
                  </SelectBox>
                )}

                <div class="Input Input--vTop Input--SelectCustomer">
                  <div class="Input-label">
                    Customer <span>(Optional)</span>
                  </div>

                  <div class="Input-content">
                    <TypeAhead
                      options={customers}
                      class="ps-in-modal"
                      searchIndices={['id', 'name', 'email', 'contact']}
                      placeholder="Select a customer"
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

                <div class="additional-options-btn">
                  <button type="button" onClick={this.handleAdditionalOptions}>
                    {showAdditionalOptions
                      ? 'Hide Options'
                      : 'Additional Options'}
                    <i
                      class={classList(
                        'i',
                        showAdditionalOptions ? 'i-arrow-up' : 'i-arrow-down'
                      )}
                    />
                  </button>
                </div>

                {showAdditionalOptions && (
                  <div class="AdditionalOptions">
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
                      onAddNew={this.handleAddNewNote}
                    />
                  </div>
                )}
              </>
            )}
          </main>

          <footer>
            {IS_MODAL_VIEW && (
              <Button type="button" onClick={onClose}>
                Cancel
              </Button>
            )}

            <Button.Primary
              type="submit"
              onClick={this.saveVirtualAccount}
              disabled={disableSubmit}
            >
              Create Virtual Account
            </Button.Primary>
          </footer>
        </Form>
      </div>
    );

    let classNames = '';

    if (descriptorLimit_BankAccount && _internals.hasBankAccount) {
      classNames += 'has_bank_account ';
    }

    if (descriptorLimit_VPA && _internals.hasVPA) {
      classNames += 'has_vpa ';
    }

    if (
      descriptorLimit_BankAccount &&
      descriptorLimit_BankAccount &&
      _internals.hasVPA &&
      _internals.hasBankAccount
    ) {
      classNames += 'has_bank_account_has_vpa';
    }

    return IS_MODAL_VIEW ? (
      <Modal
        class={classList(
          'VirtualAccountV2',
          content && 'animate-down',
          classNames
        )}
        onClose={onClose}
      >
        <ModalContent>{content}</ModalContent>
      </Modal>
    ) : (
      <div class={classList('StandAloneContainer', classNames)}>{content}</div>
    );
  }
}

function validateCustomVPA(descriptorLimit_VPA) {
  return val => {
    const isValid = validateAlphanumericWithStrictLength(
      val,
      descriptorLimit_VPA
    );

    if (isValid) return;

    return `Enter only Alphanumeric, ${descriptorLimit_VPA} characters`;
  };
}

function validateCustomBankAccountNumber(descriptorLimit_BankAccount) {
  return val => {
    const isValid = validateAlphanumericWithMaxLength(
      val,
      descriptorLimit_BankAccount
    );

    if (isValid) return;

    return `Enter only Alphanumeric, upto ${descriptorLimit_BankAccount} characters`;
  };
}

const CustomCustomerOption = ({ option }) => {
  return (
    <div class="custom-powerselect-options">
      {option.name && <b>{option.name} : </b>}
      {option.email || option.contact}
    </div>
  );
};
