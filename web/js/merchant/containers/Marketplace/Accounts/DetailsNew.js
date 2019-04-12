import React, { Component } from 'react';
import { connect } from 'react-redux';
import ActivationForm from 'merchant/containers/Activation';
import { fetchAccountApi } from 'merchant/modules/marketplace/accounts';
import Spinner from 'rzp/ui/Spinner';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import AccountCreation from 'merchant/containers/Marketplace/Accounts/New';
import { getUser } from 'merchant/store';
import { showNotification } from 'rzp/modules/notifications';
import { ToggleField } from 'merchant/components/Marketplace/Accounts/AccountsList';
import SwitchField from 'rzp/ui/Forms/SwitchField';
import * as AccountActions from 'merchant/modules/marketplace/accounts';
import { showWhenUtil } from 'merchant/components/ShowWhen';

import Amount from 'ui/Amount';

@connect(_ => ({}), {
  showNotification,
  ...AccountActions,
})
export default class Details extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  constructor(props) {
    super(props);
    this.state = {
      isLoading: false,
      account: {},
    };
  }

  onToggleDashboardAccess = (account, checked, cb) => {
    return this.context
      .confirm({
        header: `${checked ? 'Enable' : 'Disable'} Dashboard Access?`,
        message: () => (
          <div class="text-semi-muted">
            <p>
              {`Are you sure you want to ${
                checked ? 'Enable' : 'Disable'
              } dashboard access for this linked account`}
            </p>
          </div>
        ),
        affirmativeLabel: `${checked ? 'Enable' : 'Disable'}`,
        affirmativePendingLabel: `${checked ? 'Enabling' : 'Disabling'}`,
        abortLabel: 'Cancel',
        action: () => {
          return this.props
            .toggleDashboardAccess({
              dashboard_access: checked,
              accountId: account.id,
            })
            .then(resp => {
              cb(true);

              if (resp) {
                this.props.showNotification({
                  type: 'success',
                  message: `Dashboard access ${
                    checked ? 'Enabled' : 'Disabled'
                  } for merchant "${account.name}"`,
                });

                return resp;
              } else {
                throw 'Some network error has occurred';
              }
            })
            .catch(({ errors }) => {
              if (
                !errors ||
                (errors instanceof Array === true &&
                  (!errors.length || !errors[0]))
              ) {
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

  onToggleAllowRefunds = (account, checked, cb) => {
    return this.context
      .confirm({
        header: `${checked ? 'Enable' : 'Disable'} Allow Refunds?`,
        message: () => (
          <div class="text-semi-muted">
            <p>
              {`Are you sure you want to ${
                checked ? 'Enable' : 'Disable'
              } allow refunds for this linked account`}
            </p>
          </div>
        ),
        affirmativeLabel: `${checked ? 'Enable' : 'Disable'}`,
        affirmativePendingLabel: `${checked ? 'Enabling' : 'Disabling'}`,
        abortLabel: 'Cancel',
        action: () => {
          return this.props
            .toggleAllowRefunds({
              reversals_access: checked,
              accountId: account.id,
            })
            .then(resp => {
              cb(true);

              if (resp) {
                this.props.showNotification({
                  type: 'success',
                  message: `Dashboard access ${
                    checked ? 'Enabled' : 'Disabled'
                  } for merchant "${account.name}"`,
                });

                return resp;
              } else {
                throw 'Some network error has occurred';
              }
            })
            .catch(({ errors }) => {
              if (
                !errors ||
                (errors instanceof Array === true &&
                  (!errors.length || !errors[0]))
              ) {
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

  fetchData = id => {
    this.setState({
      isLoading: true,
    });

    return fetchAccountApi(id)
      .then(resp => {
        debugger;
        this.setState({
          account: resp,
          isLoading: false,
        });
      })
      .catch(e => {
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

  showEditAccountModal = account => _ => {
    this.props.openModal({
      size: 'small',
      component: (
        <AccountCreation onSave={this.onAccountEdit} accountData={account} />
      ),
    });
  };

  render() {
    const { onClose, id } = this.props,
      { isLoading, account } = this.state,
      status = account.activation_details
        ? account.activation_details.status
        : account.activated,
      user = getUser(),
      noLAEmail = user.merchants[user.current].email === account.email,
      isAllowToEdit = showWhenUtil({
        additionalCondition: user => user.isAllowedEdit('accounts'),
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
                <button
                  type="button"
                  class="close close-secondary"
                  onClick={onClose}
                >
                  <i class="i i-arrow-back" />
                  <i class="i i-close" />
                </button>
              )}
              Account ID: <strong>{id}</strong>
            </div>
            <div class="SliderPanel__Body">
              <div class="panel-body">
                <EntityDetailRow label="Email">{account.email}</EntityDetailRow>
                <EntityDetailRow label="Name">{account.name}</EntityDetailRow>
                <EntityDetailRow label="Account Status">
                  <span
                    class={`pill ${
                      status === 'activated' ? 'label-yellow' : 'label-primary'
                    }`}
                  >
                    {status === 'activated' ? 'Activated' : 'Not Activated'}
                  </span>
                </EntityDetailRow>
                <EntityDetailRow label="Refund Credits">
                  <Amount value={account.refund_credits} />
                </EntityDetailRow>
                {isAllowToEdit && (
                  <EntityDetailRow label="Dashboard Access">
                    <ToggleField
                      onEdit={this.showEditAccountModal(account)}
                      isDisabled={noLAEmail}
                    >
                      <SwitchField
                        defaultChecked={!!account.dashboard_access}
                        onChange={this.onToggleDashboardAccess}
                        disabled={noLAEmail}
                        type="prime"
                      />
                    </ToggleField>
                  </EntityDetailRow>
                )}
                {isAllowToEdit && (
                  <EntityDetailRow label="Allow Customer Refund">
                    <ToggleField
                      onEdit={this.showEditAccountModal(account)}
                      isDisabled={noLAEmail}
                    >
                      <SwitchField
                        defaultChecked={!!account.dashboard_access}
                        onChange={this.onToggleAllowRefunds}
                        disabled={noLAEmail}
                        type="prime"
                      />
                    </ToggleField>
                  </EntityDetailRow>
                )}
              </div>
            </div>
          </div>
        )}
      </div>
    );
  }
}
