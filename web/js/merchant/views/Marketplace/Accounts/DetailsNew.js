import React, { Component } from 'react';
import { connect } from 'react-redux';
import PropTypes from 'prop-types';

import ActivationForm from 'merchant/containers/Activation';
import Spinner from 'common/ui/Spinner';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import AccountCreation from 'merchant/views/Marketplace/Accounts/New';
import { showWhenUtil } from 'merchant/components/ShowWhen';
import { ModalMask } from 'common/new-ui/Modal';
import Amount from 'common/ui/Amount';
import SwitchField from 'common/ui/Forms/SwitchField';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { AccountStatusDetailsView as AccountStatus } from './components/AccountStatusLabel';

import { showNotification } from 'merchant_common/reducers/notifications';
import { ToggleField } from 'merchant/views/Marketplace/Accounts/components/AccountsList';
import { fetchBalance } from 'merchant/reducers/credits';
import * as AccountActions from 'merchant/reducers/marketplace/accounts';
import * as ModalActions from 'merchant_common/reducers/modals';
import { validateDashboardAccess, validateAllowRefundsMessages } from './List';

@connect(
  (state) => {
    return {
      user: state.session.user,
    };
  },
  {
    showNotification,
    ...AccountActions,
    ...ModalActions,
  },
)
export default class Details extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  constructor(props) {
    super(props);
    this.state = {
      isLoading: false,
      account: {},
      showActivationForm: false,
    };
  }

  onToggleDashboardAccess = (isChecked, cb) => {
    const { account } = this.state;
    const checked = !account.dashboard_access;
    const { header, message, data } = validateDashboardAccess(account, checked);

    return this.context
      .confirm({
        header,
        message,
        affirmativeLabel: `${checked ? 'Enable' : 'Disable'}`,
        affirmativePendingLabel: `${checked ? 'Enabling' : 'Disabling'}`,
        abortLabel: 'Cancel',
        action: () => {
          return this.props
            .toggleDashboardAccess(data)
            .then((resp) => {
              cb(true);

              if (resp) {
                this.props.showNotification({
                  type: 'success',
                  message: `Dashboard access ${checked ? 'Enabled' : 'Disabled'} for merchant "${
                    account.name
                  }"`,
                });

                this.setState(
                  {
                    account: {
                      ...account,
                      ...data,
                    },
                  },
                  () => {
                    this.props.updateAccount({
                      ...account,
                      ...data,
                    });
                  },
                );

                return resp;
              } else {
                throw new Error('Some network error has occurred');
              }
            })
            .catch(({ errors }) => {
              if (!errors || (errors instanceof Array === true && (!errors.length || !errors[0]))) {
                errors = 'Some network error has occurred';
              }

              this.props.showNotification({
                type: 'error',
                message: errors,
              });

              cb(false);

              throw errors;
            });
        },
      })
      .catch(() => {
        cb(false);
      }); // dummy catch to handle confirm abort rejection
  };

  onToggleAllowRefunds = (isChecked, cb) => {
    const { account } = this.state;
    const checked = !account.allow_reversals;
    const { header, message, data } = validateAllowRefundsMessages(account, checked);

    return this.context
      .confirm({
        header,
        message,
        affirmativeLabel: `${checked ? 'Enable' : 'Disable'}`,
        affirmativePendingLabel: `${checked ? 'Enabling' : 'Disabling'}`,
        abortLabel: 'Cancel',
        action: () => {
          return this.props
            .toggleAllowRefunds(data)
            .then((resp) => {
              cb(true);

              if (resp) {
                this.props.showNotification({
                  type: 'success',
                  message: `Dashboard access ${checked ? 'Enabled' : 'Disabled'} for merchant "${
                    account.name
                  }"`,
                });

                this.setState(
                  {
                    account: {
                      ...account,
                      ...data,
                    },
                  },
                  () => {
                    this.props.updateAccount({
                      ...account,
                      ...data,
                    });
                  },
                );
                return resp;
              } else {
                throw new Error('Some network error has occurred');
              }
            })
            .catch(({ errors }) => {
              if (!errors || (errors instanceof Array === true && (!errors.length || !errors[0]))) {
                errors = 'Some network error has occurred';
              }

              this.props.showNotification({
                type: 'error',
                message: errors,
              });

              cb(false);

              throw errors;
            });
        },
      })
      .catch(() => {
        cb(false);
      }); // dummy catch to handle confirm abort rejection
  };

  fetchData = (id = this.props.id) => {
    this.setState({
      isLoading: true,
    });

    return AccountActions.fetchAccountApi(id)
      .then((resp) => {
        this.setState({
          account: resp.data,
        });
        return fetchBalance(id);
      })
      .then((resp) => {
        this.setState((prevState) => {
          return {
            account: {
              ...prevState.account,
              refund_credits: resp.data.refund_credits,
              currency: resp.data.currency,
            },
            isLoading: false,
          };
        });
      })
      .catch(() => {
        this.setState({
          isLoading: false,
        });
      });
  };

  componentWillMount() {
    this.fetchData(this.props.id);
  }

  componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.fetchData(nextProps.id);
    }
  }

  showEditAccountModal = (account) => (_) => {
    this.props.openModal({
      size: 'small',
      component: <AccountCreation onSave={this.fetchData} accountData={account} />,
    });
  };

  showActivationForm = () =>
    this.setState((prevState) => {
      return { showActivationForm: !prevState.showActivationForm };
    });

  render() {
    const { onClose, id, user } = this.props;
    const { isLoading, account = {}, showActivationForm } = this.state;
    const status =
      !isLoading &&
      (account.activation_details
        ? account.activation_details && account.activation_details.status
        : account.activated);
    const noLAEmail = user.merchants[user.current].email === account.email;
    const isAllowToEdit = showWhenUtil({
      additionalCondition: (_user) => _user.isAllowedEdit('accounts'),
    });

    return (
      <div class="content-wrapper content-sm txn-details">
        {isLoading ? (
          <div class="page-spinner-container">
            <Spinner />
          </div>
        ) : (
          <div class="panel panel-default SliderPanel">
            <div class="panel-heading">
              {onClose && (
                <button type="button" class="close close-secondary" onClick={onClose}>
                  <i class="i i-arrow-back" />
                  <i class="i i-close" />
                </button>
              )}
              Account ID: <strong>{id}</strong>
            </div>
            <div class="SliderPanel__Body">
              <div class="panel-body">
                <EntityDetailRow label="Email">
                  {noLAEmail ? (
                    <button
                      class="btn btn-link no-padding"
                      onClick={this.showEditAccountModal(account)}
                    >
                      Add Email
                    </button>
                  ) : (
                    <span>
                      {account.email}
                      <a
                        class="p-l"
                        onClick={this.showEditAccountModal(account)}
                        title="Edit Email"
                      >
                        Change
                      </a>
                    </span>
                  )}
                </EntityDetailRow>
                <EntityDetailRow label="Name">{account.name}</EntityDetailRow>

                {user.isRouteCodeSupportEnabled && (
                  <EntityDetailRow label="Account Code" value={account.code} />
                )}

                <EntityDetailRow label="Account Status">
                  <AccountStatus
                    activationStatus={status}
                    showActivationForm={this.showActivationForm}
                    errorDetails={
                      account.activation_details?.bank_details_verification_error_details
                    }
                  />
                </EntityDetailRow>
                <EntityDetailRow label="Refund Credits">
                  <Amount value={account.refund_credits} currency={account.currency} />
                </EntityDetailRow>
                {isAllowToEdit && (
                  <EntityDetailRow label="Dashboard Access">
                    <ToggleField onEdit={this.showEditAccountModal(account)} isDisabled={noLAEmail}>
                      <SwitchField
                        checked={!!account.dashboard_access}
                        onChange={this.onToggleDashboardAccess}
                        disabled={noLAEmail}
                        type="prime"
                      />
                    </ToggleField>
                  </EntityDetailRow>
                )}
                {isAllowToEdit && (
                  <EntityDetailRow
                    label={
                      <span>
                        Allow Customer Refund
                        <small class="help-content" style={{ paddingLeft: '4px' }}>
                          <i class="i i-help" />
                          <Popover align="right" theme="dark">
                            <PopoverBody>
                              <div style={{ textAlign: 'left' }}>
                                This allows Linked account to refund to the customer for a transfer.
                              </div>
                            </PopoverBody>
                          </Popover>
                        </small>
                      </span>
                    }
                  >
                    <ToggleField onEdit={this.showEditAccountModal(account)} isDisabled={noLAEmail}>
                      <SwitchField
                        checked={!!account.allow_reversals}
                        onChange={this.onToggleAllowRefunds}
                        disabled={noLAEmail}
                        type="prime"
                      />
                    </ToggleField>
                  </EntityDetailRow>
                )}

                {/* TODO: Enable this once prefill account id add to direct transfers from
                {user.isDirectTransferEnabled && (
                  <>
                    <br />
                    <NavLink class="Button m-l" to="/route/transfers/direct_transfer">
                      <i class="i i-plus" />
                      Create Direct Transfer
                    </NavLink>
                  </>
                )} */}
              </div>
            </div>
          </div>
        )}
        {!isLoading && showActivationForm && (
          <ModalMask
            maskClosable={true}
            onClose={this.showActivationForm}
            class="Account-Activation"
          >
            <ActivationForm
              onClose={this.showActivationForm}
              accountId={id}
              callback={() => {
                this.props.showNotification({
                  type: 'success',
                  message: 'The account has been activated',
                });

                this.fetchData(id);
              }}
              defaultMsg={<HelpText msg="Complete the details to Activate this account." />}
            />
          </ModalMask>
        )}
      </div>
    );
  }
}

const HelpText = ({ msg, ...restProps }) => (
  <div class="help-text" {...restProps}>
    <i class="i i-info-outline" />
    <div>{msg}</div>
  </div>
);
