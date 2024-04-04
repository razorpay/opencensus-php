import { Component } from 'react';
import { Alert } from '@razorpay/blade/components';
import AsyncButton from 'react-async-button';
import { connect } from 'react-redux';
import { compose } from 'redux';
import { Field, reduxForm, formValueSelector } from 'redux-form';

import InputField from 'common/ui/Forms/InputField';
import { analyticsTrack } from 'common/utils/analytics';
import { without, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import { required, email, phone } from 'common/utils/validators';
import {
  roles,
  agentRole,
  RBLRoles,
  RegistrationLinkRoles,
  posPartnerRoles,
} from 'merchant/helpers/data';
import rolesList from 'merchant/helpers/permissions/roles-list';
import {
  trackInviteNewMemberModalLoaded,
  trackInviteNewMemberModalClicked,
} from 'merchant/views/PartnerDashboard/Home/Components/POS/analytics';
import withPartnerDashboardExperiments from 'merchant/views/PartnerDashboard/hocs/withPartnerDashboardExperiments';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

import TwoFactorVerificationOTP from 'common/ui/TwoFactorVerification/TwoFactorVerificationOTP';
import {
  triggerOtpOnEmail,
  triggerOtpOnSMS,
  triggerOtpOnBoth,
} from 'merchant_common/reducers/twoFactor';

const selector = formValueSelector('newInvitation');
@reduxForm({
  form: 'newInvitation',
  initialValues: {
    email: '',
    role: rolesList.MANAGER,
  },
})
class NewInvitation extends Component {
  state = {};
  static defaultProps = {
    ctaText: 'Submit',
  };

  // need to rename this component to something more appropriate
  constructor(props) {
    super(props);

    this.props.initialize({
      ...this.props.defaults,
    });
  }

  save = (body) => {
    const { ctaText, screen, experiments, successMsg, onFormSubmit, isRenderedFromPartnerRoute } =
      this.props;
    const is_edit = this.props.ctaText === 'Update Invitation';
    analyticsTrack({
      objectName: is_edit ? 'invitation update popup' : 'invite new member popup',
      actionName: 'clicked',
      screen: 'my account',
      properties: {
        action: 'send invitation',
        location: 'manage team',
        test: 'test',
        role: body?.role,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    if (experiments?.isPartnershipsForPosEnabled) {
      trackInviteNewMemberModalClicked({
        ctaClicked: ctaText,
        screen: screen || window.location.pathname,
        isRenderedFromPartnerRoute,
      });
    }

    return onFormSubmit(body)
      .then(() => {
        if (!is_edit) {
          selfServeTrackSuccess({
            selfServeAction: 'New Member Invited',
            page: 'Team',
            screen: 'My Account',
          });
        }
        analyticsTrack({
          objectName: 'invite new member',
          actionName: 'status',
          screen: 'my account',
          properties: {
            location: 'manage team',
            status: 'success',
            role: body?.role,
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
        this.props.showNotification({
          type: 'success',
          message: typeof successMsg === 'function' ? successMsg(body) : successMsg,
        });
        this.props.closeModal();
      })
      .catch((err) => {
        analyticsTrack({
          objectName: 'invite new member status',
          actionName: 'clicked',
          screen: 'my account',
          properties: {
            location: 'manage team',
            status: 'failure',
            failureReason: err?.errors[0],
            role: body?.role,
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });
      });
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

  onCloseClick = () => {
    this.props.closeModal();
  };

  sendVerificationOtp = (isResend = false) => {
    return () => {
      const { hasBothEmailAndMobile, hasOnlyEmail } = this.getOTPDestination();

      const triggerOTP = hasBothEmailAndMobile
        ? triggerOtpOnBoth
        : hasOnlyEmail
        ? triggerOtpOnEmail
        : triggerOtpOnSMS;

      return triggerOTP()
        .then(({ data }) => {
          this.setState({
            token: data.token,
          });
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

  onOtpConfirm = ({ otp }) => {
    const { selectedRole, invitedEmail, senderName } = this.props;
    const payload = {
      sender_name: senderName,
      role: selectedRole,
      email: invitedEmail,
      otp,
      action: 'second_factor_auth',
      token: this.state.token,
    };
    return this.save(payload);
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
  componentDidMount() {
    const { screen, experiments, isRenderedFromPartnerRoute } = this.props;
    if (experiments?.isPartnershipsForPosEnabled) {
      trackInviteNewMemberModalLoaded({
        screen: screen || window.location.pathname,
        isRenderedFromPartnerRoute,
      });
    }
  }

  filterRoles = () => {
    const rolesToRemove = [];

    if (!this.props.user.isEnhancedEPOSEnabled) {
      rolesToRemove.push(rolesList.SELLERAPP_PLUS);
    }

    // owner role cannot be assigned to anyone
    rolesToRemove.push(rolesList.OWNER);

    return without(roles, rolesToRemove);
  };

  render() {
    const {
      handleSubmit,
      selectedRole,
      user,
      visibleFields,
      experiments,
      isInviteTeamMember2faEnabled,
      isRenderedFromPartnerRoute,
      ...props
    } = this.props;

    let ROLES = this.filterRoles();

    if (user.isAgentRole) {
      ROLES = { ...ROLES, ...agentRole };
    } else if (user.role === rolesList.RBL_SUPERVISOR) {
      ROLES = { rbl_agent: RBLRoles.rbl_agent }; // RBL Supervisor can only invite rbl_agent
    } else if (user.isRBLRoleEnabled) {
      ROLES = { ...ROLES, ...RBLRoles }; // Allowed only for roles with edit access as per permissions map
    }

    if (user.isRegistrationLinkRoleEnabled) {
      ROLES = { ...ROLES, ...RegistrationLinkRoles };
    }

    if (experiments?.isPartnershipsForPosEnabled || isRenderedFromPartnerRoute) {
      ROLES = { ...ROLES, ...posPartnerRoles };
    }
    const shouldShowNonPOSAlert =
      isRenderedFromPartnerRoute && !!selectedRole && selectedRole !== rolesList.PARTNER_AGENT;

    return (
      <form>
        <div>
          <div class="form-group">
            <label>Member Details</label>
            {visibleFields.email && (
              <div class="input-container">
                <Field
                  name="email"
                  component={InputField}
                  class="form-control"
                  placeholder="Email"
                  autoFocus={true}
                  validate={[
                    required(),
                    email('Invalid Email'),
                    (value) => {
                      if (value === this.props.user.user.email) {
                        return "You can't invite yourself";
                      }

                      return null;
                    },
                  ]}
                />
              </div>
            )}
            {visibleFields.contactMobile && (
              <div class="input-container">
                <Field
                  name="contact_mobile"
                  component={InputField}
                  class="form-control"
                  placeholder="Phone Number"
                  validate={[
                    required(),
                    phone('Invalid Mobile'),
                    (value) => {
                      if (value === this.props.user.user.contact_mobile) {
                        return "You can't invite yourself";
                      }

                      return null;
                    },
                  ]}
                />
              </div>
            )}
          </div>
          {visibleFields.role && (
            <>
              <div class="form-group">
                <label>Role</label>
                <div class="input-container">
                  <Field name="role" component="select" class="form-control">
                    {Object.keys(ROLES).map((role) => (
                      <option key={role} value={role}>
                        {ROLES[role].label}
                      </option>
                    ))}
                  </Field>
                </div>
              </div>
              <div class="form-group">
                {!shouldShowNonPOSAlert && ROLES[selectedRole]?.desc ? (
                  <div class="alert alert-info text-center">{ROLES[selectedRole].desc}</div>
                ) : null}
                {shouldShowNonPOSAlert ? (
                  <Alert
                    isDismissible={false}
                    color="negative"
                    description={
                      "You're adding a role which is associated with your merchant profile"
                    }
                  />
                ) : null}
              </div>
            </>
          )}
          <div class="form-group">
            <AsyncButton
              class="btn btn-primary btn-block"
              text={props.ctaText}
              type="submit"
              pendingText={isInviteTeamMember2faEnabled ? 'Sending OTP...' : 'Processing...'}
              onClick={
                isInviteTeamMember2faEnabled
                  ? handleSubmit(this.sendVerificationOtp())
                  : handleSubmit(this.save)
              }
            />
          </div>
        </div>
      </form>
    );
  }
}

const mapStateToProps = (state) => {
  return {
    user: state.session.user,
    selectedRole: selector(state, 'role'),
    invitedEmail: selector(state, 'email'),
    senderName: selector(state, 'sender_name'),
    ...state.session,
  };
};

export default compose(
  withPartnerDashboardExperiments,
  connect(mapStateToProps, {
    showNotification,
    closeModal,
    openModal,
  }),
)(NewInvitation);
