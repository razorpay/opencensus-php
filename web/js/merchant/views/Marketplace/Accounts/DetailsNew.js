import React, { Component } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { compose } from 'redux';

import { ModalMask } from 'common/new-ui/Modal';
import { withSplitzService } from 'common/splitz';
import Amount from 'common/ui/Amount';
import Popover, { PopoverBody } from 'common/ui/Popover';
import Spinner from 'common/ui/Spinner';
import { isExperimentActive } from 'common/utils/rzp-utils';
import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import { showWhenUtil } from 'merchant/components/ShowWhen';
import ActivationForm from 'merchant/containers/Activation';
import { fetchBalance } from 'merchant/reducers/credits';
import * as AccountActions from 'merchant/reducers/marketplace/accounts';
import AccountCreation from 'merchant/views/Marketplace/Accounts/New';
import { ToggleField } from 'merchant/views/Marketplace/Accounts/components/AccountsList';
import * as ModalActions from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

import { validateDashboardAccess, validateAllowRefundsMessages } from './List';
import { AccountStatusDetailsView as AccountStatus } from './components/AccountStatusLabel';

class Details extends Component {
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

  UNSAFE_componentWillMount() {
    this.fetchData(this.props.id);
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.fetchData(nextProps.id);
    }
  }

  showEditAccountModal = (account, trackSelfServe) => (_) => {
    if (trackSelfServe) {
      selfServeTrackInitiate({
        selfServeAction: 'Email added',
        page: 'Account',
        screen: 'Route',
      });
    }
    this.props.openModal({
      size: 'small',
      component: <AccountCreation onSave={this.fetchData} accountData={account} />,
    });
  };

  showActivationForm = () => {
    const { splitz, user } = this.props;
    const { account } = this.state;

    const {
      abExperiments: { enable_modular_onboarding_linked_account = {} },
    } = splitz;

    if (
      isExperimentActive(enable_modular_onboarding_linked_account) &&
      user.country_code === 'MY'
    ) {
      // Redirect to modular onboarding if experiment is active
      window.open(
        `${window.CURLEC_LINKED_ACCOUNT_ONBOARDING_URL}?accountId=${account.id}`,
        '_blank',
      );
    } else {
      this.setState((prevState) => {
        return { showActivationForm: !prevState.showActivationForm };
      });
    }
  };

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

    const isRouteDSEnabled = user.isRouteDSEnabled;
    const isCreationDisabled = user.isRouteLinkedAccountCreationDisabled || isRouteDSEnabled;

    return (
      <div className="content-wrapper content-sm txn-details">
        {isLoading ? (
          <div className="page-spinner-container">
            <Spinner />
          </div>
        ) : (
          <div className="panel panel-default SliderPanel">
            <div className="panel-heading">
              {onClose && (
                <button type="button" className="close close-secondary" onClick={onClose}>
                  <i className="i i-arrow-back" />
                  <i className="i i-close" />
                </button>
              )}
              Account ID: <strong>{id}</strong>
            </div>
            <div className="SliderPanel__Body">
              <div className="panel-body">
                <EntityDetailRow label="Email">
                  {noLAEmail ? (
                    <span>
                      {isCreationDisabled && (
                        <Popover align="top" theme="dark">
                          <PopoverBody>
                            This action is not allowed for your business type
                          </PopoverBody>
                        </Popover>
                      )}
                      <button
                        className="btn btn-link no-padding"
                        onClick={this.showEditAccountModal(account, true)}
                        disabled={isCreationDisabled}
                      >
                        Add Email
                      </button>
                    </span>
                  ) : (
                    <span>
                      <span className="p-r">{account.email}</span>
                      <span>
                        {isCreationDisabled && (
                          <Popover align="top" theme="dark">
                            <PopoverBody>
                              This action is not allowed for your business type
                            </PopoverBody>
                          </Popover>
                        )}
                        <button
                          className="btn btn-link no-padding"
                          onClick={this.showEditAccountModal(account, true)}
                          title="Edit Email"
                          disabled={isCreationDisabled}
                        >
                          Change
                        </button>
                      </span>
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
                    errorDetails={account.activation_details?.bank_details_verification_error}
                    isCreationDisabled={isCreationDisabled}
                  />
                </EntityDetailRow>
                <EntityDetailRow label="Refund Credits">
                  <Amount value={account.refund_credits ?? 0} currency={account.currency} />
                </EntityDetailRow>
                {isAllowToEdit && (
                  <EntityDetailRow label="Dashboard Access">
                    <ToggleField
                      onEdit={this.showEditAccountModal(account)}
                      isLAEmailAbsent={noLAEmail}
                      isLACreationDisabled={isCreationDisabled}
                      checked={!!account.dashboard_access}
                      onChange={this.onToggleDashboardAccess}
                      isDisabled={isRouteDSEnabled}
                      isDashboard
                    />
                  </EntityDetailRow>
                )}
                {isAllowToEdit && (
                  <EntityDetailRow
                    label={
                      <span>
                        Allow Customer Refund
                        <small className="help-content" style={{ paddingLeft: '4px' }}>
                          <i className="i i-help" />
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
                    <ToggleField
                      onEdit={this.showEditAccountModal(account)}
                      isLAEmailAbsent={noLAEmail}
                      isLACreationDisabled={isCreationDisabled}
                      checked={!!account.allow_reversals}
                      onChange={this.onToggleAllowRefunds}
                      isDisabled={isRouteDSEnabled}
                    />
                  </EntityDetailRow>
                )}

                {/* TODO: Enable this once prefill account id add to direct transfers from
                {user.isDirectTransferEnabled && (
                  <>
                    <br />
                    <NavLink className="Button m-l" to="/route/transfers/direct_transfer">
                      <i className="i i-plus" />
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
            className="Account-Activation"
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
  <div className="help-text" {...restProps}>
    <i className="i i-info-outline" />
    <div>{msg}</div>
  </div>
);

export default compose(
  connect(
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
  ),
  withSplitzService,
)(Details);
