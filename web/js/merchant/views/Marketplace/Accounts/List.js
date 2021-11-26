import { connect } from 'react-redux';
import PropTypes from 'prop-types';

import { RZPFeatures } from 'merchant/helpers/data';

import HeaderAction from 'common/ui/HeaderAction';
import Pager from 'common/ui/Pager';
import Alert from 'common/ui/Forms/Alert';

import * as ModalActions from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

import { luminateRow } from 'merchant/reducers/app';
import * as AccountActions from 'merchant/reducers/marketplace/accounts';

import DocsLink from 'merchant/components/DocsLink';
import ShowWhen, { showWhenUtil } from 'merchant/components/ShowWhen';
import TakeATourButton from 'merchant/components/QuickGuide/TakeATourButton';
import AccountsList from 'merchant/views/Marketplace/Accounts/components/AccountsList';
import AccountsListFilter from 'merchant/views/Marketplace/Accounts/components/AccountsListFilter';

import ListContainer from 'merchant/containers/ListContainer';
import AccountCreation from 'merchant/views/Marketplace/Accounts/New';
import AccountDetails from 'merchant/views/Marketplace/Accounts/Details';

@connect(
  (state) => {
    return {
      ...state.accounts,
      user: state.session.user,
    };
  },
  {
    ...AccountActions,
    ...ModalActions,
    showNotification,
    luminateRow,
  },
)
export default class AccountsListContainer extends ListContainer {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  onToggleDashboardAccess = (account, cb) => {
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

                this.props.updateAccount({
                  ...account,
                  ...data,
                });

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

  onToggleAllowRefunds = (account, cb) => {
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

                this.props.updateAccount({
                  ...account,
                  ...data,
                });

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

  fetchList({ id, ...rest }) {
    if (id && id.indexOf('acc_') > -1) {
      id = id.replace('acc_', '');
    }
    return this.props.fetchAccounts({ id, ...rest });
  }

  fetchAccounts = (skip, count) => {
    this.props.fetchAccounts({ skip, count });
  };

  onAccountCreation = (account) => {
    this.resetPagination();
    this.showAccountDetailsModal(account); // Open activation modal
  };

  resetPagination = () => {
    // Reset pagination and fetch results of updated pagination
    const paginationSkip = 0;
    this.setState({ skip: paginationSkip });
    this.fetchAccounts(paginationSkip, this.state.count);
  };

  showAddAccountModal = () => {
    this.props.openModal({
      size: 'small',
      component: <AccountCreation onSave={this.onAccountCreation} />,
    });
  };

  showEditAccountModal = (account) => {
    this.props.openModal({
      size: 'small',
      component: <AccountCreation onSave={this.resetPagination} accountData={account} />,
    });
  };

  showAccountDetailsModal = (account) => {
    this.props.closeModal();
    this.setState({ showAccountDetailsFor: account.id });
  };

  highlightRowAndClose = (accountId) => {
    this.props.luminateRow(accountId);
    this.setState({ showAccountDetailsFor: null });
    this.props.closeModal();
  };

  exportAccountsCSV = () => {
    this.props.showNotification({
      type: 'info',
      message: 'Your file will download shortly',
      hidePrevious: true,
    });

    return this.props
      .exportAccountsCSV()
      .then((response) => {
        window.location.href = response.data.url;
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors,
          hidePrevious: true,
        });
      });
  };

  render() {
    const { loading, accounts, user } = this.props;
    const status = this.state.status;

    return (
      <div class="LinkedAccountsList content-wrapper">
        <HeaderAction>
          <div class="btn-toolbar pull-right">
            <ShowWhen additionalCondition={(_user) => !_user.isOrgAxis}>
              <TakeATourButton feature={RZPFeatures.ROUTE} />
            </ShowWhen>

            <DocsLink title="Documentation" url="https://razorpay.com/docs/route/" />

            <button class="btn btn-default" onClick={this.exportAccountsCSV}>
              <i class="i i-download" />
              <span>Export All (CSV)</span>
            </button>

            <ShowWhen
              additionalCondition={(_user) => _user.isAllowedEdit('accounts') && !_user.isOrgAxis}
            >
              <button class="btn btn-primary" onClick={this.showAddAccountModal}>
                <i class="i i-plus" />
                <span>Add Account</span>
              </button>
            </ShowWhen>
          </div>
        </HeaderAction>

        <AccountsListFilter
          form="accountsListFilter"
          count={this.state.count}
          onSubmit={this.search}
          isRouteCodeSupportEnabled={user.isRouteCodeSupportEnabled}
        />

        <Alert type={status.type} message={status.message} />

        <AccountsList
          accounts={accounts}
          isLoading={loading}
          isDirectTransferEnabled={user.isDirectTransferEnabled}
          showEditAccountModal={this.showEditAccountModal}
          onEdit={this.showAccountDetailsModal}
          isRouteCodeSupportEnabled={user.isRouteCodeSupportEnabled}
          onToggleDashboardAccess={
            showWhenUtil({
              additionalCondition: (_user) => _user.isAllowedEdit('accounts'),
            })
              ? this.onToggleDashboardAccess
              : undefined
          }
          onToggleAllowRefunds={
            showWhenUtil({
              additionalCondition: (_user) => _user.isAllowedEdit('accounts'),
            })
              ? this.onToggleAllowRefunds
              : undefined
          }
        />

        <Pager
          count={this.state.count}
          skip={this.state.skip}
          length={accounts.length}
          onClick={this.paginate}
        />
        {this.state.showAccountDetailsFor && (
          <AccountDetails
            accountId={this.state.showAccountDetailsFor}
            onClose={this.highlightRowAndClose}
            onSubmitSuccessCB={() => {
              this.props.showNotification({
                type: 'success',
                message: 'The account has been activated',
              });

              this.fetchAccounts(this.state.skip, this.state.count);
            }}
          />
        )}
      </div>
    );
  }
}

export function validateDashboardAccess(account, checked) {
  let header = `${checked ? 'Enable' : 'Disable'} Dashboard Access?`;
  let message = `Are you sure you want to ${
    checked ? 'Enable' : 'Disable'
  } dashboard access for this linked account`;
  let data = {
    dashboard_access: checked,
    accountId: account.id,
  };

  if (account.allow_reversals && !checked) {
    header = 'Also Disable Customer Refunds?';
    message =
      'Disabling Dashboard Access will also disable the refund to customer to the Linked Account.';
    data = {
      accountId: account.id,
      dashboard_access: checked,
      allow_reversals: checked,
    };
  }

  return {
    header,
    message,
    data,
  };
}

export function validateAllowRefundsMessages(account, checked) {
  let header = `${checked ? 'Enable' : 'Disable'} Allow Refunds`;
  let message = `Are you sure you want to ${
    checked ? 'Enable' : 'Disable'
  } allow refunds for this linked account`;
  let data = {
    allow_reversals: checked,
    accountId: account.id,
  };

  if (!account.dashboard_access && checked) {
    header = 'Also enable Dashboard Access?';
    message =
      'Enabling Refund to customer will also enable Dashboard access to the Linked Account.';
    data = {
      allow_reversals: checked,
      accountId: account.id,
      dashboard_access: checked,
    };
  }

  return {
    header,
    message,
    data,
  };
}
