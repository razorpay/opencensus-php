import { Component } from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';
import RTracking from 'react-tracking';
import { Field, reduxForm, formValueSelector, change } from 'redux-form';
import AsyncButton from 'react-async-button';
import PropTypes from 'prop-types';
import InputField from 'common/ui/Forms/InputField';
import ModalHeader from 'common/ui/ModalHeader';
import Alert from 'common/ui/Forms/Alert';
import { required } from 'common/utils/validators';
import * as AccountActions from 'merchant/reducers/marketplace/accounts';
import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import SwitchField from 'common/ui/Forms/SwitchField';
import ShowWhen from 'merchant/components/ShowWhen';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import {
  triggerOtpOnEmail,
  triggerOtpOnSMS,
  triggerOtpOnBoth,
} from 'merchant_common/reducers/twoFactor';
import TwoFactorVerificationOTP from 'common/ui/TwoFactorVerification/TwoFactorVerificationOTP';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { withSplitzService } from 'common/splitz';
import { is2faRouteExperimentEnabled } from 'common/utils/rzp-utils';

// Decorate with connect to read form values
const selector = formValueSelector('newAccount');

@reduxForm({
  form: 'newAccount',
  initialValues: {
    account: true,
  },
})
@RTracking(() => window.rzpQ.component('AddAccount'))
class AddAccount extends Component {
  static contextTypes = {
    // eslint-disable-next-line
    confirm: PropTypes.func,
  };

  state = {
    errors: null,
    token: null,
  };

  UNSAFE_componentWillMount() {
    const { accountData, user } = this.props;
    let email = null;
    //check whether the LA has its own email or not
    if (accountData && user.merchants[user.current].email !== accountData.email) {
      email = accountData.email;
    }

    if (accountData) {
      this.props.initialize({
        name: accountData.name,
        ...(email && { email: accountData.email }),
        code: accountData.code,
      });
    }
  }

  componentDidMount() {
    this.props.tracking.trackEvent(
      window.rzpQ.routeActions().interaction('route.linked_account.add_account.started'),
    );
  }

  save = (props) => {
    const { accountData } = this.props;
    const requestData = { ...props };
    const reqFunc = accountData ? this.props.updateEmail : this.props.saveAccount;

    if (accountData) {
      requestData.accountId = accountData.id;
      delete requestData.name;
    } else if (typeof requestData.dashboard_access !== 'undefined') {
      requestData.dashboard_access = !!requestData.dashboard_access;
    }

    if (typeof requestData.allow_reversals !== 'undefined') {
      requestData.allow_reversals = !!requestData.allow_reversals;
    }

    this.props.tracking.trackEvent(
      window.rzpQ.routeActions().initiated('route.linked_account.add_account', {
        source: 'merchant_dashboard',
      }),
    );

    return reqFunc(requestData)
      .then((account) => {
        this.props.onSave(account);
        selfServeTrackSuccess({
          selfServeAction: accountData ? 'Email added' : 'Route Account created',
          page: 'Account',
          screen: 'Route',
        });
        this.props.showNotification({
          type: 'success',
          message: accountData ? 'Email added successfully' : 'Account created successfully',
        });
        this.props.closeModal();
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors.errors,
        });
        this.setState({
          errors,
        });
      });
  };

  confirmDashboardAccess = (checked) => {
    const { fromChange, allow_reversals } = this.props;

    fromChange('dashboard_access', checked);

    if (!checked && allow_reversals) {
      this.context
        .confirm({
          header: 'Also Disable Customer Refunds?',
          message: () => (
            <div class="text-semi-muted">
              <p>
                Disabling Dashboard Access will also disable the refund to customer to the Linked
                Account.
              </p>
            </div>
          ),
          affirmativeLabel: 'Disable',
          affirmativePendingLabel: 'Disabling',
          abortLabel: 'Cancel',
          action: () => {
            fromChange('dashboard_access', false);
            fromChange('allow_reversals', false);
          },
        })
        .catch((_) => {
          fromChange('dashboard_access', checked);
        });
    }
  };

  confirmAllowRefunds = (checked) => {
    const { dashboard_access, fromChange } = this.props;

    fromChange('allow_reversals', checked);

    if (checked && !dashboard_access) {
      this.context
        .confirm({
          header: 'Also enable Dashboard Access?',
          message: () => (
            <div class="text-semi-muted">
              <p>
                Enabling Refund to customer will also enable Dashboard access to the Linked Account.
              </p>
            </div>
          ),
          affirmativeLabel: 'Enable',
          affirmativePendingLabel: 'Enabling',
          abortLabel: 'Cancel',
          action: () => {
            fromChange('dashboard_access', true);
            fromChange('allow_reversals', true);
          },
        })
        .catch((_) => {
          fromChange('dashboard_access', false);
          fromChange('allow_reversals', false);
        });
    }
  };

  closeModal = () => {
    this.props.closeModal();

    this.props.tracking.trackEvent(
      window.rzpQ.routeActions().dropped('route.linked_account.add_account'),
    );
  };

  onOtpConfirm = ({ otp }) => {
    const payload = {
      account: true,
      name: this.state.name,
      email: this.state.email,
      otp,
      token: this.state.token,
      action: 'second_factor_auth',
    };
    return this.save({ ...payload });
  };

  onCloseClick = () => {
    this.props.closeModal();
  };

  getOTPDestination = () => {
    const { user } = this.props.user;
    const hasOnlyEmail = Boolean(user.email && user.confirmed);
    const hasOnlyMobile = Boolean(user.contact_mobile && user.contact_mobile_verified);
    const hasBothEmailAndMobile = hasOnlyEmail && hasOnlyMobile;

    return {
      hasOnlyEmail,
      hasOnlyMobile,
      hasBothEmailAndMobile,
    };
  };

  show2faModal = () => {
    const { user } = this.props.user;
    const { hasBothEmailAndMobile, hasOnlyEmail, hasOnlyMobile } = this.getOTPDestination();

    const isNewAccountAndSettingsPage = this.props.user?.isAccountAndSettingsRevampEnabled;

    this.props.openModal({
      size: 'small',
      component: (
        <TwoFactorVerificationOTP
          onConfirm={this.onOtpConfirm}
          onClose={this.onCloseClick}
          onResend={this.sendVerificationOtp(true)}
          title="Invite new member"
          renderMessage={() => (
            <p class="m-b">
              Inviting new member requires you to enter OTP sent over to your{' '}
              {hasOnlyEmail && (
                <>
                  registered email address <strong>{user.email}</strong>
                </>
              )}
              {hasBothEmailAndMobile && ' and '}
              {hasOnlyMobile && (
                <>
                  registered phone number <strong>{user.contact_mobile}</strong>
                </>
              )}
            </p>
          )}
          isNewAccountAndSettingsPage={isNewAccountAndSettingsPage}
        />
      ),
    });
  };

  sendVerificationOtp = (isResend = false) => {
    return () => {
      const { hasBothEmailAndMobile, hasOnlyEmail } = this.getOTPDestination();

      let triggerOTP;
      if (hasBothEmailAndMobile) {
        triggerOTP = triggerOtpOnBoth;
      } else if (hasOnlyEmail) {
        triggerOTP = triggerOtpOnEmail;
      } else triggerOTP = triggerOtpOnSMS;

      return triggerOTP()
        .then(({ data }) => {
          this.setState({
            token: data.token,
          });
          // Dont show when 2fa modal is already being shown
          if (!isResend) {
            this.show2faModal();
          }
        })
        .catch(({ errors }) => {
          this.props.showNotification({
            type: 'error',
            message: errors,
          });
        });
    };
  };

  render() {
    const { handleSubmit, user, accountData, allow_reversals, dashboard_access } = this.props;
    let noLAEmail;

    if (!accountData) {
      noLAEmail = !this.state.email || user.merchants[user.current].email === this.state.email;
    }

    const { abExperiments } = this.props.splitz;

    const is2faExperimentActive = is2faRouteExperimentEnabled(abExperiments);

    return (
      <div class="accounts-edit-new">
        <ModalHeader
          title={!!accountData ? 'Edit Account' : 'Add Account'}
          onCloseClick={this.closeModal}
        />

        <div class="modal-body">
          <Alert type="error" message={this.state.errors} />

          <form onSubmit={handleSubmit(this.save)}>
            <div class="form-group">
              <label class="label-required">Account Name</label>
              <div>
                <Field
                  name="name"
                  component={InputField}
                  class="form-control"
                  autoFocus={true}
                  validate={required()}
                  disabled={!!accountData}
                  onChange={(e) => this.setState({ name: e.target.value })}
                />
                <small class="help-block">
                  The business/individual name for the account, which will appear on all reports
                </small>
              </div>
            </div>

            <div class="form-group">
              <label>Account Email</label>
              <div>
                <Field
                  name="email"
                  component="input"
                  class="form-control"
                  value={this.state.email}
                  onChange={(e) => this.setState({ email: e.target.value })}
                />
                <small class="help-block">
                  Your linked-account user can access their dashboard using this email id. You may
                  Add/Edit the email later.
                </small>
              </div>
            </div>

            {user.isRouteCodeSupportEnabled && (
              <div class="form-group">
                <label>Account Code</label>
                <div>
                  <Field
                    name="code"
                    component="input"
                    class="form-control"
                    value={this.state.code}
                    onChange={(e) => this.setState({ code: e.target.value })}
                    maxLength={20}
                  />
                  <small class="help-block">
                    Maximum 20 characters. Alphanumeric and <span class="special-chars">_ . -</span>{' '}
                    only
                  </small>
                </div>
              </div>
            )}

            {!accountData && (
              <ShowWhen additionalCondition={(user) => user.isAllowedEdit('accounts')}>
                <div class="form-group">
                  <EnableDashboardField isDisabled={noLAEmail}>
                    <div class="rzpCheckbox">
                      <label for="dashboard_access">
                        <span>Dashboard Access</span>
                      </label>
                      <div class="pull-right">
                        <SwitchField
                          name="dashboard_access"
                          id="dashboard_access"
                          type="prime"
                          onChange={this.confirmDashboardAccess}
                          checked={dashboard_access}
                          disabled={noLAEmail}
                        />
                      </div>
                    </div>
                    <div class="rzpCheckbox">
                      <label for="allow_reversals">
                        <span>Allow customer Refunds</span>
                      </label>
                      <div class="pull-right">
                        <SwitchField
                          name="allow_reversals"
                          id="allow_reversals"
                          onChange={this.confirmAllowRefunds}
                          checked={allow_reversals}
                          type="prime"
                          disabled={noLAEmail}
                        />
                      </div>
                    </div>
                  </EnableDashboardField>
                </div>
              </ShowWhen>
            )}

            <div class="Modal__actions">
              <AsyncButton
                class="btn btn-primary btn-block"
                text={!!accountData ? 'Update' : 'Add'}
                pendingText="Adding..."
                onClick={
                  is2faExperimentActive
                    ? handleSubmit(this.sendVerificationOtp())
                    : handleSubmit(this.save)
                }
              />
            </div>
          </form>
        </div>
      </div>
    );
  }
}

AddAccount.defaultProps = {
  onSave: () => {},
};

const EnableDashboardField = ({ children, isDisabled }) => {
  if (isDisabled) {
    return (
      <small class="help-content">
        {children}
        <Popover align="top" parentQuerySelector=".accounts-edit-new" theme="dark">
          <PopoverBody>
            <div>
              Please add Email id to enable dashboard access and customer refunds for this linked
              account
            </div>
          </PopoverBody>
        </Popover>
      </small>
    );
  }

  return children;
};

export default compose(
  withSplitzService,
  connect(
    (state) => {
      const dashboard_access = selector(state, 'dashboard_access');
      const allow_reversals = selector(state, 'allow_reversals');
      return {
        user: state.session.user,
        dashboard_access,
        allow_reversals,
      };
    },
    {
      ...AccountActions,
      ...ModalActions,
      ...NotificationsActions,
      fromChange: (...args) => change('newAccount', ...args),
      closeModal,
      openModal,
      showNotification,
    },
  ),
)(AddAccount);
