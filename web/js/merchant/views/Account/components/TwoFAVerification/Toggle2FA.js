import isEmpty from 'lodash/isEmpty';
import PropTypes from 'prop-types';
import qs from 'query-string';
import { Component, createRef } from 'react';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';
import { withRouter } from 'common/deprecated/withRouter';

import { classList, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

import SwitchField from 'common/ui/Forms/SwitchField';
import { updateSession } from 'merchant/reducers/session';
import { updateSelfContact } from 'merchant/reducers/team';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

import TriggerOnQueryParamMatch from 'common/ui/TriggerOnQueryParamMatch';
import { analyticsTrack } from 'common/utils/analytics';
import { selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import UpdateSelfContactMobile from 'merchant/views/Account/Profile/components/UpdateSelfContactMobile';
import {
  ACTION_QUERY_PARAM_KEY,
  ENABLE_2FA,
} from 'merchant/views/Account/Profile/deeplink-constants';
import PasswordVerification from './PasswordVerification';

@connect((state) => ({ user: state.session.user }), {
  openModal,
  closeModal,
  updateSelfContact,
  updateSession,
  showNotification,
})
class Toggle2FA extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  switchBtnRef = createRef();

  showModal = (component) => {
    this.props.openModal({
      size: 'small',
      component,
      queryParams: {
        [ACTION_QUERY_PARAM_KEY]: ENABLE_2FA,
      },
    });
  };

  verifyPassword = (flag) => {
    const user = this.props.user;
    const secondFactorAuthPayload = { second_factor_auth: flag };

    if (user.isCriticalRouteExperimentEnabled) {
      this.sendUpdateSecondFactorAuthRequest(secondFactorAuthPayload);
    } else {
      this.showModal(
        <PasswordVerification
          closeModal={this.abort}
          onSubmit={this.sendUpdateSecondFactorAuthRequest}
          dataSentWithPassword={secondFactorAuthPayload}
          enable={flag}
        />,
      );
    }
  };

  sendUpdateSecondFactorAuthRequest = (data) => {
    const { toggle2FaEnforcement, getToggle2FaSuccessMsg } = this.props;
    return toggle2FaEnforcement(data)
      .then((response) => {
        const { second_factor_auth } = response.data;
        const twoFaStatus = second_factor_auth ? 'on' : 'off';
        const message = getToggle2FaSuccessMsg(twoFaStatus);
        selfServeTrackSuccess({
          selfServeAction: '2fa Switch',
          page: 'Team',
          screen: 'My Account',
        });
        analyticsTrack({
          objectName: `2fa switch`,
          actionName: 'result',
          screen: 'my account',
          properties: {
            type: second_factor_auth ? 'Enable 2FA' : 'Disable 2FA',
            status: 'success',
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
        this.props.showNotification({
          type: 'success',
          message,
        });
        this.success();
      })
      .catch(({ errors }) => {
        const error = (errors || [])[0];
        if (error === 'User 2FA setup is required') {
          // this is for restricted mode merchants
          // when all team members don't have a verified mobile number
          this.props.closeModal();
          this.showAllUsers2faSetupRequired();
        } else {
          this.props.showNotification({
            type: 'error',
            message: error,
          });
        }
      });
  };

  verifyMobile = (flag) => {
    this.showModal(
      <UpdateSelfContactMobile
        onSuccess={() => {
          this.verifyPassword(flag);
        }}
        onClose={this.abort}
      />,
    );
  };

  success = () => {
    this.props.closeModal();
    this.actionCompleted(true);
  };

  abort = () => {
    this.props.closeModal();
    //Any intermediate modal closure or cancel will abort the process
    this.actionCompleted(false);
  };

  showAllUsers2faSetupRequired = () => {
    this.context.confirm({
      header: '2-step verification',
      message:
        'To enable 2-step verification all your team members should have phone numbers associated to their account.',
      abortLabel: 'Close',
      affirmativeLabel: 'Okay',
      abort: this.abort,
      action: this.abort,
    });
  };

  confirmEnable = ({ action, flag }) => {
    this.context.confirm({
      header: 'Enable 2-step verification',
      message: this.props.confirmEnableMessage,
      affirmativeLabel: 'Yes, enable it',
      abort: (...e) => {
        analyticsTrack({
          objectName: `${this.props.eventPrefix ? this.props.eventPrefix : '2fa'} account ${
            flag ? 'enable' : 'disable'
          } confirmation popup`,
          actionName: 'clicked',
          screen: 'my account',
          properties: {
            location: this.props.location,
            action: 'cancel',
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
        return this.abort(...e);
      },
      action: () => {
        analyticsTrack({
          objectName: `${this.props.eventPrefix ? this.props.eventPrefix : '2fa'} account ${
            flag ? 'enable' : 'disable'
          } confirmation popup`,
          actionName: 'clicked',
          screen: 'my account',
          properties: {
            location: this.props.location,
            action: 'yes',
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
        return action?.(flag);
      },
    });
  };

  confirmDisable({ action, flag }) {
    this.context.confirm({
      header: 'Disable 2-step verification',
      message: this.props.confirmDisableMessage,
      affirmativeLabel: 'Yes, disable it',
      abortLabel: "No, Don't!",
      abort: this.abort,
      action: () => action(flag),
    });
  }

  toggle2FA = (flag, options) => {
    //Hold the toggle state until a final API call is made & resolved
    // eslint-disable-next-line no-async-promise-executor
    return new Promise((resolve) => {
      this.actionCompleted = resolve;
      let action;
      // skipping VerifyPassword flow if SetPasswordModal is triggered in TwoFactorVerificationProvider
      if (options?.skipVerifyPassword) {
        action = () => {
          this.sendUpdateSecondFactorAuthRequest({ second_factor_auth: flag });
        };
      } else {
        action = this.verifyPassword;
      }

      if (flag) {
        this.confirmEnable({ action, flag });
      } else {
        this.confirmDisable({ action, flag });
      }
      this.removeActionQueryParams();
    });
  };

  onToggleChange = (flag, cb, options = {}) =>
    this.toggle2FA(flag, options).then((completed) => {
      //Set the sate in redux store to reflect the new changes
      if (completed) {
        this.props.onToggleComplete(flag);
        this.trackEvent(flag);
      }
      cb(completed);
    });

  removeActionQueryParams = () => {
    const params = qs.parse(this.props.location.search);
    Object.keys({
      [ACTION_QUERY_PARAM_KEY]: ENABLE_2FA,
    }).forEach((queryParamKey) => {
      delete params[queryParamKey];
    });
    this.props.history.replace({ search: isEmpty(params) ? '' : qs.stringify(params) });
  };

  enable2faTriggeredByDeepLink = () => {
    const { twoFaEnabled } = this.props;
    const changeSwitchState = this.switchBtnRef.current?.controlledStateChange;
    // trigger only when 2fa is disabled
    if (!twoFaEnabled && changeSwitchState) {
      changeSwitchState({
        checked: true,
        isActionPending: true,
      });
      this.props.onToggleChange(this.onToggleChange)(true, (isSuccess) => {
        if (!isSuccess) {
          setTimeout(
            () =>
              changeSwitchState({
                checked: false,
                isActionPending: false,
              }),
            100,
          ); // Revert if false
        } else {
          changeSwitchState({ isActionPending: false });
        }
      });
    }
  };
  render() {
    const { twoFaEnabled, shouldOnlyToggle } = this.props;
    return (
      <TriggerOnQueryParamMatch
        queryParamsMapping={[
          {
            key: ACTION_QUERY_PARAM_KEY,
            value: ENABLE_2FA,
            trigger: this.enable2faTriggeredByDeepLink,
          },
        ]}
      >
        {shouldOnlyToggle ? (
          <SwitchField
            ref={this.switchBtnRef}
            defaultChecked={twoFaEnabled}
            // temporarily using onChange callback from props
            // otherwise this component will require access to both
            // new and old context
            onChange={this.props.onToggleChange(this.onToggleChange)}
            type="prime"
          />
        ) : (
          <div className="panel panel-default">
            <div className="panel-heading">
              {this.props.renderTitle && this.props.renderTitle()}

              <span className="toggler-btn">
                <SwitchField
                  ref={this.switchBtnRef}
                  defaultChecked={twoFaEnabled}
                  // temporarily using onChange callback from props
                  // otherwise this component will require access to both
                  // new and old context
                  onChange={this.props.onToggleChange(this.onToggleChange)}
                  type="prime"
                />
                <strong className={classList('m-l', twoFaEnabled ? 'text-primary' : 'text-faded')}>
                  {twoFaEnabled ? 'Enabled' : 'Disabled'}
                </strong>
              </span>
            </div>

            <div className="panel-body">
              <form className="form-horizontal">
                <div className="description">{this.props.renderDescription()}</div>
              </form>
            </div>
          </div>
        )}
      </TriggerOnQueryParamMatch>
    );
  }

  trackEvent = (twoFaEnabled) => {
    const event = twoFaEnabled ? 'enable' : 'disable';
    this.props.tracking.trackEvent(
      window.rzpQ.now().onbr().success(`dash.2fa_${event}`, {
        source: 'Toggle2FA',
        sessionId: window.session_id,
      }),
    );
  };
}

// eslint-disable-next-line babel/new-cap
export default RTracking(() => {
  return window.rzpQ.component('Toggle2FA');
})(withRouter(Toggle2FA));
