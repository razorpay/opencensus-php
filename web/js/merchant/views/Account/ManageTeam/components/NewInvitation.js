import { Component } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm, formValueSelector } from 'redux-form';
import AsyncButton from 'react-async-button';

import InputField from 'common/ui/Forms/InputField';

import { required, email, phone } from 'common/utils/validators';
import { roles, agentRole, RBLRoles, RegistrationLinkRoles } from 'merchant/helpers/data';
import { without, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

import { showNotification } from 'merchant_common/reducers/notifications';
import { closeModal } from 'merchant_common/reducers/modals';
import rolesList from 'merchant/helpers/permissions/roles-list';
import { analyticsTrack } from 'common/utils/analytics';

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
    const { successMsg } = this.props;
    return this.props
      .onFormSubmit(body)
      .then(() => {
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

  filterRoles = () => {
    const rolesToRemove = [];

    if (!this.props.user.isEnhancedEPOSEnabled) {
      rolesToRemove.push(rolesList.SELLERAPP_PLUS);
    }

    return without(roles, rolesToRemove);
  };

  render() {
    const { handleSubmit, selectedRole, user, visibleFields, ...props } = this.props;

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

export default connect(mapStateToProps, {
  showNotification,
  closeModal,
})(NewInvitation);
