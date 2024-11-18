import { Box } from '@razorpay/blade/components';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';

import { withRouter } from 'common/deprecated/withRouter';
import { withSplitzService } from 'common/splitz';
import Alert from 'common/ui/Forms/Alert';
import Pager from 'common/ui/Pager';
import ProductWrapper from 'common/ui/ProductWrapper';
import TwoFactorVerificationContext from 'common/ui/TwoFactorVerification/TwoFactorVerificationContext';
import { is2FaExperimentEnabled, isExperimentActive } from 'common/utils/rzp-utils';
import { selfServeTrackInitiate, selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import CustomerFeeBearerPopover from 'merchant/components/CustomerFeeBearerPopover';
import DocsLink from 'merchant/components/DocsLink';
import TakeATourButton from 'merchant/components/QuickGuide/TakeATourButton';
import ShowWhen, { showWhenUtil } from 'merchant/components/ShowWhen';
import TestModeBanner from 'merchant/components/TestModeBanner';
import { FEE_BEARER_TYPES } from 'merchant/constants/feeBearer';
import ListContainer from 'merchant/containers/ListContainer';
import { RZPFeatures } from 'merchant/helpers/data';
import { isOrgFeatureExist } from 'merchant/models/User';
import { luminateRow } from 'merchant/reducers/app';
import * as AccountActions from 'merchant/reducers/marketplace/accounts';
import AccountDetails from 'merchant/views/Marketplace/Accounts/Details';
import AccountCreation from 'merchant/views/Marketplace/Accounts/New';
import AccountsList from 'merchant/views/Marketplace/Accounts/components/AccountsList';
import AccountsListFilter from 'merchant/views/Marketplace/Accounts/components/AccountsListFilter';
import {
  linkedAccountTabOpenedAnalytics,
  linkedAccountDashboardAccessGrantedAnalytics,
} from 'merchant/views/Marketplace/MarketplaceAnalytics';
import { navItems } from 'merchant/views/Marketplace/NavItems';
import * as ModalActions from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

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
class AccountsListContainer extends ListContainer {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  componentDidMount() {
    linkedAccountTabOpenedAnalytics(this.props.user.id);
  }

  onToggleDashboardAccess = (account, cb) => {
    const { toggleDashboardAccess, showNotification, updateAccount } = this.props;
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
          return toggleDashboardAccess(data)
            .then((resp) => {
              cb(true);

              if (resp) {
                showNotification({
                  type: 'success',
                  message: `Dashboard access ${checked ? 'Enabled' : 'Disabled'} for merchant "${
                    account.name
                  }"`,
                });
                if (checked) {
                  linkedAccountDashboardAccessGrantedAnalytics(this.props.user.id);
                }

                updateAccount({
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

              showNotification({
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
    const { toggleAllowRefunds, showNotification, updateAccount } = this.props;
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
          return toggleAllowRefunds(data)
            .then((resp) => {
              cb(true);

              if (resp) {
                showNotification({
                  type: 'success',
                  message: `Dashboard access ${checked ? 'Enabled' : 'Disabled'} for merchant "${
                    account?.name
                  }"`,
                });

                updateAccount({
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

              showNotification({
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
    const { fetchAccounts } = this.props;
    if (id && id.indexOf('acc_') > -1) {
      id = id.replace('acc_', '');
    }
    return fetchAccounts({ id, ...rest });
  }

  fetchAccounts = (skip, count) => {
    const { fetchAccounts } = this.props;
    fetchAccounts({ skip, count });
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
    const { openModal } = this.props;
    selfServeTrackInitiate({
      selfServeAction: 'Route Account created',
      page: 'Account',
      screen: 'Route',
    });
    openModal({
      size: 'small',
      component: <AccountCreation onSave={this.onAccountCreation} />,
    });
  };

  showEditAccountModal = (account) => {
    const { openModal } = this.props;
    openModal({
      size: 'small',
      component: <AccountCreation onSave={this.resetPagination} accountData={account} />,
    });
  };

  showAccountDetailsModal = (account) => {
    const { closeModal, user } = this.props;
    const {
      abExperiments: { enable_modular_onboarding_linked_account = {} },
    } = this.props.splitz;
    closeModal();
    if (
      typeof window !== 'undefiend' &&
      isExperimentActive(enable_modular_onboarding_linked_account) &&
      user.isOrgCurlec
    ) {
      // Redirect to modular onboarding if experiment is active
      window.location.href = `${window.CURLEC_LINKED_ACCOUNT_ONBOARDING_URL}?accountId=${account.id}`;
    } else {
      this.setState({ showAccountDetailsFor: account.id });
    }
  };

  highlightRowAndClose = (accountId) => {
    const { luminateRow, closeModal } = this.props;
    luminateRow(accountId);
    this.setState({ showAccountDetailsFor: null });
    closeModal();
  };

  exportAccountsCSV = () => {
    const { showNotification, exportAccountsCSV } = this.props;
    showNotification({
      type: 'info',
      message: 'Your file will download shortly',
      hidePrevious: true,
    });
    selfServeTrackInitiate({
      selfServeAction: 'Route Accounts List Downloaded',
      page: 'Account',
      screen: 'Route',
    });
    return exportAccountsCSV()
      .then((response) => {
        window.location.href = response.data.url;
        selfServeTrackSuccess({
          selfServeAction: 'Route Accounts List Downloaded',
          page: 'Account',
          screen: 'Route',
        });
      })
      .catch(({ errors }) => {
        showNotification({
          type: 'error',
          message: errors,
          hidePrevious: true,
        });
      });
  };

  handleAccountAddition = ({ criticalFlow, is2FaExperimentActive }) => {
    if (is2FaExperimentActive) {
      criticalFlow({
        enforceVerifyOtp: true,
        modes: ['live', 'test'],
        onUserTwoFaVerified: this.showAddAccountModal,
      });
    } else {
      this.showAddAccountModal();
    }
  };

  render() {
    const {
      loading,
      accounts,
      user,
      showNotification,
      isPlatformFeeTabEnabled,
      isPartnerPlatformFeeEnabled,
      splitz,
    } = this.props;
    const status = this.state.status;
    const feeBearer = user.merchant.fee_bearer;
    const isCustomerFeeBearer = feeBearer === FEE_BEARER_TYPES.CUSTOMER;
    const isCreationDisabled = user.isRouteLinkedAccountCreationDisabled || isCustomerFeeBearer;
    const is2FaExperimentActive = is2FaExperimentEnabled(splitz.abExperiments);

    return (
      <ProductWrapper
        tabsData={navItems(user, isPlatformFeeTabEnabled, isPartnerPlatformFeeEnabled)}
        extra={
          <>
            <ShowWhen additionalCondition={(_user) => !_user.isOrgAxis}>
              <TakeATourButton feature={RZPFeatures.ROUTE} />
            </ShowWhen>

            <DocsLink title="Documentation" url="https://razorpay.com/docs/route/" />

            <button type="button" className="btn btn-default" onClick={this.exportAccountsCSV}>
              <i className="i i-download" />
              <span>Export All (CSV)</span>
            </button>
            <ShowWhen
              additionalCondition={(_user) =>
                _user.isAllowedEdit('accounts') &&
                !isOrgFeatureExist('block_account_update') &&
                !_user.isOrgCurlec
              }
            >
              <Box display="inline-block">
                <TwoFactorVerificationContext.Consumer>
                  {({ criticalFlow }) => (
                    <button
                      type="button"
                      className="btn btn-primary"
                      onClick={() =>
                        this.handleAccountAddition({ criticalFlow, is2FaExperimentActive })
                      }
                      disabled={isCreationDisabled}
                      title={
                        isCreationDisabled &&
                        !isCustomerFeeBearer &&
                        'Linked account creation is not allowed for your business type'
                      }
                    >
                      <i className="i i-plus" />
                      <span>Add Account</span>
                    </button>
                  )}
                </TwoFactorVerificationContext.Consumer>
                {isCustomerFeeBearer && <CustomerFeeBearerPopover feature="Route" />}
              </Box>
            </ShowWhen>
          </>
        }
      >
        <content>
          <div className="LinkedAccountsList content-wrapper">
            <TestModeBanner />

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
              isCreationDisabled={user.isRouteLinkedAccountCreationDisabled}
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
                  showNotification({
                    type: 'success',
                    message: 'The account has been activated',
                  });

                  this.fetchAccounts(this.state.skip, this.state.count);
                }}
              />
            )}
          </div>
        </content>
      </ProductWrapper>
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

export default withSplitzService(withRouter(AccountsListContainer));
