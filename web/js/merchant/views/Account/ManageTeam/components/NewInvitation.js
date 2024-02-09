import { Component } from 'react';
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
import { closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

const selector = formValueSelector('newInvitation');
@reduxForm({
  form: 'newInvitation',
  initialValues: {
    email: '',
    role: rolesList.MANAGER,
  },
})
class NewInvitation extends Component {
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
    if (this.props.experiments?.isPartnershipsForPosEnabled) {
      trackInviteNewMemberModalClicked({
        ctaClicked: this.props.ctaText,
        screen: this.props.screen,
      });
    }
    const { successMsg } = this.props;
    return this.props
      .onFormSubmit(body)
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

  componentDidMount() {
    if (this.props.experiments?.isPartnershipsForPosEnabled) {
      trackInviteNewMemberModalLoaded({ screen: this.props.screen });
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
    const { handleSubmit, selectedRole, user, visibleFields, experiments, ...props } = this.props;

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

    if (experiments?.isPartnershipsForPosEnabled) {
      ROLES = { ...ROLES, ...posPartnerRoles };
    }

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
                {ROLES[selectedRole] && ROLES[selectedRole].desc ? (
                  <div class="alert alert-info text-center">{ROLES[selectedRole].desc}</div>
                ) : null}
              </div>
            </>
          )}
          <div class="form-group">
            <AsyncButton
              class="btn btn-primary btn-block"
              text={props.ctaText}
              type="submit"
              pendingText="Processing..."
              onClick={handleSubmit(this.save)}
            />
          </div>
        </div>
      </form>
    );
  }
}

const mapStateToProps = (state) => {
  return {
    selectedRole: selector(state, 'role'),
    ...state.session,
  };
};

export default compose(
  withPartnerDashboardExperiments,
  connect(mapStateToProps, {
    showNotification,
    closeModal,
  }),
)(NewInvitation);
