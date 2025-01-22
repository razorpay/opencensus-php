import React from 'react';
import PropTypes from 'prop-types';
import { withRouter } from 'common/deprecated/withRouter';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';

import { Modal, ModalContent } from 'common/new-ui/Modal';
import { TypeAhead } from 'react-power-select';
import SelectBox from 'common/new-ui/SelectBox';
import Button from 'common/new-ui/Button';
import QuickAdd from 'common/ui/Select/QuickAdd';
import Form from 'common/new-ui/Form';
import Input from 'common/new-ui/Input';
import Spinner from 'common/ui/Spinner';
import Popover, { PopoverBody } from 'common/ui/Popover';

import { classList } from 'common/utils/rzp-utils';
import { validateAlphanumeric } from 'common/utils/validators';
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
import VPAPrefixModal from './VPAPrefixModal';
import ConfigureBankAccountsModal from 'merchant/views/SmartCollect/VirtualAccounts/components/Modals/ConfigureBankAccounts';

import BankImage from 'assets/bank.svg';
import UpiImage from 'assets/upi.svg';

import {
  DESCRIPTOR_LENGTH_BANK_ACCOUNT,
  DESCRIPTOR_LENGTH_VPA,
  getStyle_DescriptorInput_BankAccount,
  getStyle_AddOnBefore_BankAccount,
  getStyle_DescriptorInput_VPA,
  getStyle_AddOnBefore_VPA,
  getStyle_AddOnAfter_VPA,
} from 'merchant/views/SmartCollect/VirtualAccounts/helpers';

import { fetchFeatureStatus } from 'merchant/reducers/config';
import { selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import { isExperimentEnabled } from 'common/splitz/utils';
import { withSplitzService } from 'common/splitz';

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
      ...state.config.config,
      user: state.session.user,
      isTestMode: state.session.mode === 'test',
      va_config: state.virtualaccounts.va_config || {},
      org: state.session.org,
    };
  },
  {
    luminateRow,
    showNotification,
    saveVirtualAccount,
    ...ModalActions,
    fetchConfigForVirtualAccount,
    fetchCustomersForAutocomplete,
    fetchFeatureStatus,
  },
)
@RTracking(() => window.rzpQ.component('CreateVirtualAccount'))
class CreateVirtualAccount extends React.Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  constructor(props) {
    super();

    const {
      abExperiments: { enable_smartcollect_vpa_option },
    } = props.splitz;
    const showVpaSC = isExperimentEnabled(enable_smartcollect_vpa_option);

    this.state = {
      notes: {},
      close_by: null,
      _internals: {
        hasBankAccount: !props.user.isVACreationBankAccountDisabled,
        hasVPA: !props.isTestMode && showVpaSC,
      },
      isLoading: true,
      isUpdating: false,
      showAdditionalOptions: false,
      descriptors: {
        vpa: '',
        bank_account: '',
      },
      allowedPayers: [],
      isTPVOrg: false,
      isTPVMid: false,
    };
  }

  componentDidMount() {
    this.fetchDataForVA();

    this.track('open');

    // check TPV org feature
    if (this.props.org.features.indexOf('axis_tpv') > -1) {
      // eslint-disable-next-line react/no-did-mount-set-state
      this.setState({
        isTPVOrg: true,
      });
      // check TPV MID feature
      this.props
        .fetchFeatureStatus(this.props.user.id, 'axis_tpv_enable')
        .then((detail) => {
          if (detail.data?.status) {
            this.setState({
              isTPVMid: true,
            });
          }
        })
        .catch((err) => {
          if (err) {
            this.props.showNotification({
              type: 'error',
              message: err.errors[0],
            });
          }
        });
    }
  }

  componentWillUnmount() {
    this.track('close');
  }

  track = (event, options) => {
    this.props.tracking.trackEvent(
      window.rzpQ.smartCollect().interaction(`smartcollect.va.create.${event}`, options),
    );
  };

  fetchDataForVA = () => {
    const promiseList = [];

    const isVAConfigAvl = this.props.va_config.bank_account || this.props.va_config.vpa;

    if (!isVAConfigAvl) {
      promiseList.push(this.props.fetchConfigForVirtualAccount());
    }

    promiseList.push(this.props.fetchCustomersForAutocomplete());

    Promise.all(promiseList)
      .then(() => {
        this.setState(
          {
            isLoading: false,
          },
          this.setModalHeight,
        );
      })
      .catch(() => {
        this.setState({
          isLoading: false,
        });
      });
  };

  setModalHeight = () => {
    setTimeout(() => {
      const formEle = document.querySelector('.VirtualAccount--CreateV2 .form-container');
      const modalEle = document.querySelector('.Modal-container--VirtualAccountV2');

      if (modalEle) {
        modalEle.style['max-height'] = `${formEle.offsetHeight + 156}px`;
      }
    });
  };

  handleSubmit = (formData) => {
    this.track('advance.notes.submit');

    const { descriptorVPA, descriptorBankAccount, description } = formData;
    const { notes, close_by, _internals, customer, allowedPayers } = this.state;

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

    if (allowedPayers && allowedPayers.length) {
      reqPayload.allowed_payers = allowedPayers.map((bankAccount) => ({
        type: 'bank_account',
        bank_account: bankAccount,
      }));
    }

    this.setState({
      isUpdating: true,
    });

    return this.props
      .saveVirtualAccount(reqPayload)
      .then((virtualAccount) => {
        this.setState({
          isUpdating: false,
        });

        this.props.closeModal();

        const entityId = virtualAccount.id;

        this.props.luminateRow(entityId);

        const redirectUrl = `/virtualaccounts/${entityId}`;
        this.props.history.push(redirectUrl);

        this.track('advance.notes.submit.success');

        selfServeTrackSuccess({
          selfServeAction: 'Customer Identifier Created',
          page: 'Virtualaccounts',
          screen: 'Smart Collect',
        });
        // On success, Show account details summary
        this.props.openModal({
          size: 'small',
          className: 'VirtualAccountSummary',
          component: (
            <AccountDetailsSummary
              modalTitle="Customer Identifier Created"
              closeModal={() => {
                this.props.closeModal();

                this.track('submit.success.close');
              }}
              virtualAccount={virtualAccount}
              onCopy={() => {
                this.track('submit.success.copy');
              }}
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

        this.track('advance.notes.submit.fail', {
          message: errors[0],
        });
      });
  };

  selectCustomerAndCloseModal = (customer) => {
    this.setState({
      customer,
    });

    this.track('customer.add');

    this.props.closeModal();
  };

  openCreateCustomerModal = ({ searchTerm = '' }) => {
    this.track('customer.change');

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
    if (this.state.notes.length > notes.length) {
      this.track('advance.notes.cancel');
    }

    this.setState({ notes });
  };

  handleSelectCustomer = ({ option }) => {
    this.setState({
      customer: option,
    });

    if (option) {
      this.track('customer.select');

      return;
    }

    this.track('customer.cancel');
  };

  updateDate = (newDate) => {
    this.track('advance.autoclose', {
      selected: !!newDate,
    });

    this.setState({ close_by: newDate });
  };

  handlePaymentMethod = (name) => () => {
    this.setState(
      (prevState) => ({
        _internals: {
          ...prevState._internals,
          [name]: !prevState._internals[name],
        },
      }),
      this.setModalHeight,
    );

    this.track(name.replace('has', ''), {
      checked: !this.state._internals[name],
    });
  };

  handleDescriptor = (name) => (event) => {
    this.setState(
      (prevState) => ({
        descriptors: {
          ...prevState.descriptors,
          [name]: event.target.value,
        },
      }),
      this.setModalHeight,
    );
  };

  handleAdditionalOptions = () => {
    if (this.state.showAdditionalOptions) {
      const formTopEle = document.querySelector('.VirtualAccount--CreateV2 .payment-method-label');

      if (formTopEle) {
        formTopEle.scrollIntoView({
          behavior: 'smooth',
          block: 'center',
        });
      }

      setTimeout(() => {
        this.setState({
          showAdditionalOptions: false,
        });

        this.track('advance.hide');
      }, 100);

      return true;
    }

    this.track('advance.show');

    this.setState(
      {
        showAdditionalOptions: true,
      },
      () => {
        setTimeout(() => {
          const scrollEle = document.querySelector('.AdditionalOptions');

          if (scrollEle) {
            scrollEle.scrollIntoView({
              behavior: 'smooth',
              block: 'nearest',
            });
          }
        }, 100);
      },
    );
    return false;
  };

  handleAddNewNote = (freshPairs) => {
    this.track('advance.notes');
    setTimeout(() => {
      const newNoteElement = document.getElementsByName(
        `notes[${freshPairs.length - 1}][value]`,
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
    this.track('prefix');

    this.props.openModal({
      size: 'medium',
      className: 'VPAPrefixModal',
      component: (
        <VPAPrefixModal
          track={(event, options) => {
            this.track(`prefix.${event}`, options);
          }}
        />
      ),
    });
  };

  openConfigureBankAccountsModal = () => {
    this.props.openModal({
      size: 'medium',
      className: 'ConfigureBankAccounts',
      component: (
        <ConfigureBankAccountsModal
          bankAccounts={this.state.allowedPayers}
          onSave={(allowedPayers) => {
            this.setState({
              allowedPayers,
            });
          }}
        />
      ),
    });
  };

  handleRemoveAllowedPayers = () => {
    this.track('advance.tpv.remove');

    this.context.confirm({
      header: 'Remove Third Party Validation?',
      message:
        'Authorised account(s) linked to this customer identifier will be removed and payments will be accepted from all accounts.',
      abortLabel: 'No, Dont’t',
      affirmativeLabel: 'Yes, Remove',
      affirmativePendingLabel: 'Removing...',
      action: () => {
        this.setState({
          allowedPayers: [],
        });
      },
    });
  };

  render() {
    const { customers = [], onClose, va_config, user, isTestMode } = this.props;

    const IS_MODAL_VIEW = !!onClose;

    const { _internals, showAdditionalOptions, isLoading, isUpdating, descriptors, allowedPayers } =
      this.state;

    const disableSubmit =
      isLoading || (!_internals.hasVPA && !_internals.hasBankAccount) || isUpdating;

    const { bank_account: bankAccountConfig, vpa: vpaConfig } = va_config;

    let descriptorLimit_BankAccount, descriptorLimit_VPA;

    if (bankAccountConfig && bankAccountConfig.isDescriptorEnabled) {
      descriptorLimit_BankAccount =
        DESCRIPTOR_LENGTH_BANK_ACCOUNT - bankAccountConfig.prefix.length;
    }

    if (vpaConfig && vpaConfig.isDescriptorEnabled && vpaConfig.merchant_prefix) {
      descriptorLimit_VPA = DESCRIPTOR_LENGTH_VPA - vpaConfig.merchant_prefix.length;
    }

    const showBankAccountDescriptor = !!descriptorLimit_BankAccount && _internals.hasBankAccount;
    let showVPADescriptor = !!descriptorLimit_VPA && _internals.hasVPA;
    let showVPAPrefix = _internals.hasVPA;

    if (user.isUnregisteredBusiness && vpaConfig && vpaConfig.merchant_prefix === 'payto00000') {
      showVPADescriptor = false;
      showVPAPrefix = false;
    }

    const bankAccountLengthCheck = vpaConfig?.account_number_length || descriptorLimit_BankAccount;

    const {
      abExperiments: { enable_smartcollect_vpa_option },
    } = this.props.splitz;
    const showVpaSC = isExperimentEnabled(enable_smartcollect_vpa_option);
    // have added this to diable the VPA option from smart collect create customer identifier

    const content = (
      <div class="VirtualAccount--CreateV2 Wizard">
        <Form onChange={this.props.onChange} onSubmit={this.handleSubmit} ref={this.setRefForm}>
          <main>
            <div class="form-container">
              <div class="form-title">Create Customer Identifier</div>

              {isLoading ? (
                <div class="page-spinner-container">
                  <Spinner />
                </div>
              ) : (
                <>
                  <label class="payment-method-label">
                    Methods to accept payments in this customer identifier
                  </label>

                  <SelectBox
                    name="hasBankAccount"
                    class="SelectBox--Outline"
                    onClick={this.handlePaymentMethod('hasBankAccount')}
                    defaultChecked={_internals.hasBankAccount}
                    label={
                      <div>
                        <img src={BankImage} width="16px" height="16px" /> Bank Transfers (NEFT,
                        RTGS, IMPS)
                      </div>
                    }
                    description={
                      !_internals.hasBankAccount &&
                      'Get customer identifier details to accept fund transfers.'
                    }
                  >
                    {showBankAccountDescriptor && (
                      <Input
                        name="descriptorBankAccount"
                        size="vpa_custom"
                        description={
                          <>
                            <div class="remaining-count">
                              {descriptors.bank_account.length} / {bankAccountLengthCheck}
                            </div>
                            <br />
                            If left blank, a customer identifier number will be auto generated
                          </>
                        }
                        style={getStyle_DescriptorInput_BankAccount(va_config)}
                        onChange={this.handleDescriptor('bank_account')}
                        validator={validateCustomBankAccountNumber(bankAccountLengthCheck)}
                        addonBefore={
                          <span style={getStyle_AddOnBefore_BankAccount(va_config)}>
                            {bankAccountConfig.prefix}
                          </span>
                        }
                        onBlur={(event) => {
                          this.track('bank_number');

                          const message = validateCustomBankAccountNumber(bankAccountLengthCheck)(
                            event.target.value,
                          );

                          if (message) {
                            this.track('bank_account.error', {
                              message,
                            });
                          }
                        }}
                      />
                    )}
                  </SelectBox>

                  {!isTestMode && showVpaSC && (
                    <SelectBox
                      name="hasVPA"
                      class="SelectBox--Outline"
                      onClick={this.handlePaymentMethod('hasVPA')}
                      defaultChecked={_internals.hasVPA}
                      label={
                        <div>
                          <img src={UpiImage} width="16px" height="16px" /> UPI Transfers (GPay,
                          PhonePe, etc.)
                        </div>
                      }
                      description={
                        <>
                          {_internals.hasVPA &&
                          showVPAPrefix &&
                          vpaConfig &&
                          vpaConfig.merchant_prefix ? (
                            <>
                              To update <strong>{`"${vpaConfig.merchant_prefix}"`}</strong> prefix{' '}
                              <a onClick={this.openVPAPrefixModal}>click here</a>
                            </>
                          ) : null}
                          {!_internals.hasVPA && <>Get a VPA to accept fund transfers via UPI.</>}
                        </>
                      }
                    >
                      {showVPADescriptor && (
                        <Input
                          autoRender
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
                          onBlur={(event) => {
                            this.track('upi_id');

                            const message = validateCustomVPA(descriptorLimit_VPA)(
                              event.target.value,
                            );
                            if (message) {
                              this.track('upi_id.error', {
                                message,
                              });
                            }
                          }}
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
                        placeholder="Select or Add a New Customer"
                        showClear={true}
                        selected={this.state.customer}
                        selectedOptionLabelPath="selectedDisplayName"
                        optionComponent={CustomCustomerOption}
                        onChange={this.handleSelectCustomer}
                        afterOptionsComponent={(props) => (
                          <QuickAdd {...props} onClick={this.openCreateCustomerModal} />
                        )}
                        onOpen={() => {
                          this.track('customer');
                        }}
                      />
                    </div>
                  </div>

                  <div
                    class={classList('additional-options-btn', showAdditionalOptions && 'btn-hide')}
                    onClick={this.handleAdditionalOptions}
                  >
                    {showAdditionalOptions ? (
                      <div class="heading">Hide Advance Options</div>
                    ) : (
                      <div>
                        <div class="heading">View Advance Options</div>
                        <div class="description">
                          {' '}
                          Third Party Validation, Auto Close, Description, etc.{' '}
                        </div>
                      </div>
                    )}
                    <i
                      class={classList(
                        'i',
                        showAdditionalOptions ? 'i-chevron-up' : 'i-chevron-down',
                      )}
                    />
                  </div>

                  {showAdditionalOptions && (
                    <div class="AdditionalOptions">
                      {!this.state.isTPVOrg || (this.state.isTPVOrg && this.state.isTPVMid) ? (
                        <div class="third-party-validation">
                          <div>
                            <strong>Third Party Validation</strong>

                            <span class="m-l">
                              <i
                                class="i i-info-outline"
                                onClick={() => {
                                  this.track('advance.tpv_info');
                                }}
                              />
                              <Popover
                                align="top"
                                theme="dark"
                                parentQuerySelector={IS_MODAL_VIEW && '.VirtualAccount--CreateV2'}
                              >
                                <PopoverBody>
                                  Only authorised accounts will be able to make payments to this
                                  customer identifier.
                                </PopoverBody>
                              </Popover>
                            </span>
                          </div>

                          <div class="description">
                            {!!allowedPayers.length
                              ? `Configured with ${allowedPayers.length} authorised accounts.`
                              : 'Not Configured'}

                            <div class="actions">
                              {!!allowedPayers.length ? (
                                <>
                                  <button
                                    type="button"
                                    class="btn-link"
                                    onClick={() => {
                                      this.openConfigureBankAccountsModal();
                                      this.track('advance.tpv.edit');
                                    }}
                                  >
                                    Edit
                                  </button>{' '}
                                  |
                                  <button
                                    type="button"
                                    class="btn-link"
                                    onClick={this.handleRemoveAllowedPayers}
                                  >
                                    Remove
                                  </button>
                                </>
                              ) : (
                                <button
                                  type="button"
                                  class="btn-link"
                                  onClick={() => {
                                    this.openConfigureBankAccountsModal();
                                    this.track('advance.tpv.configure');
                                  }}
                                >
                                  Configure
                                </button>
                              )}
                            </div>
                          </div>
                        </div>
                      ) : null}
                      <hr />

                      <Input.TextareaAutoResize
                        class="Input--vTop"
                        name="description"
                        label="Customer Identifier Description"
                        description="Description is shown only on the dashboard and not to customers"
                        onBlur={() => {
                          this.track('advance.description');
                        }}
                      />

                      <hr />

                      <Input.DateTime
                        class="Input--vTop"
                        label="Close By"
                        checkboxFieldLabel="Disable Auto Close"
                        onChange={this.updateDate}
                        description="You won’t be able to receive payments after the specified date"
                        isInline
                        onDateChange={() => {
                          this.track('advance.autoclose.date');
                        }}
                        onTimeChange={() => {
                          this.track('advance.autoclose.time');
                        }}
                      />

                      <hr />

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
            </div>
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
              Create Customer Identifier
            </Button.Primary>
          </footer>
        </Form>
      </div>
    );

    return IS_MODAL_VIEW ? (
      <Modal class={classList('VirtualAccountV2', content && 'animate-down')} onClose={onClose}>
        <ModalContent>{content}</ModalContent>
      </Modal>
    ) : (
      <div class="StandAloneContainer">{content}</div>
    );
  }
}

function validateCustomVPA(descriptorLimit_VPA) {
  return (value) => {
    const isValidLength = value.length === descriptorLimit_VPA;
    if (!isValidLength) {
      return `Must contain ${descriptorLimit_VPA} characters`;
    }

    const isValidAlphanumeric = validateAlphanumeric(value);
    if (!isValidAlphanumeric) {
      return 'Special characters not allowed';
    }
    return false;
  };
}

function validateCustomBankAccountNumber(descriptorLimit_BankAccount) {
  return (value) => {
    const isValidLength = value.length <= descriptorLimit_BankAccount;
    if (!isValidLength) {
      return `Must be less than ${descriptorLimit_BankAccount} characters`;
    }

    const isValidAlphanumeric = validateAlphanumeric(value);
    if (!isValidAlphanumeric) {
      return 'Special characters not allowed';
    }
    return '';
  };
}

export default withSplitzService(withRouter(CreateVirtualAccount));
