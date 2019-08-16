import { Component } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm, formValueSelector } from 'redux-form';
import AsyncButton from 'react-async-button';

import InputField from 'rzp/ui/Forms/InputField';

import { required, email, phone } from 'rzp/utils/validators';
import { roles, agentRole, RBLRoles } from 'rzp/utils/constants';
import { without } from 'rzp/utils/rzp-utils';

import { showNotification } from 'rzp/modules/notifications';
import { classList } from 'common/util';

const selector = formValueSelector('newInvitation');
@connect(
  state => {
    return {
      selectedRole: selector(state, 'role'),
      ...state.session,
    };
  },
  {
    showNotification,
  }
)
@reduxForm({
  form: 'newInvitation',
  initialValues: {
    email: '',
    role: 'manager',
  },
})
export default class NewInvitation extends Component {
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
  save = body => {
    let user = this.props.user.user;

    return this.props
      .onFormSubmit(body)
      .then(() => {
        this.props.showNotification({
          type: 'success',
          message: this.props.successMsg(body),
        });
        this.props.onSuccess();
      })
      .catch(err => {
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });
      });
  };

  filterRoles = () => {
    const rolesToRemove = ['owner'];

    if (!this.props.user.isEnhancedEPOSEnabled) {
      rolesToRemove.push('sellerapp_plus');
    }

    return without(roles, rolesToRemove);
  };

  render() {
    const {
      handleSubmit,
      selectedRole,
      user,
      visibleFields,
      ...props
    } = this.props;

    let ROLES = this.filterRoles();

    if (user.isAgentRole) {
      ROLES = { ...ROLES, ...agentRole };
    } else {
      if (user.role === 'rbl_supervisor') {
        ROLES = { rbl_agent: RBLRoles.rbl_agent }; // RBL Supervisor can only invite rbl_agent
      } else if (user.isRBLRoleEnabled) {
        ROLES = { ...ROLES, ...RBLRoles }; // Allowed only for roles with edit access as per permissions map
      }
    }

    return (
      <div>
        <div class="form-group Form--vertical">
          <label>Member Details</label>
          {/* this should be configurable from props */}
          {visibleFields.email && (
            <div class="input-container top-rounded">
              <i class="i i-email" />
              <Field
                name="email"
                component={InputField}
                class="form-control"
                placeholder="Email"
                autoFocus={true}
                validate={[
                  required(),
                  email('Invalid Email'),
                  value => {
                    if (value === this.props.user.user.email) {
                      return "You can't invite yourself";
                    }
                  },
                ]}
              />
            </div>
          )}
          {/* this also should be configurable using props */}
          {visibleFields.contactMobile && (
            <div
              class={classList('input-container bottom_rounded', {
                ['no-top-border']: visibleFields.email,
              })}
            >
              <i class="i i-phone" />
              <Field
                name="contact_mobile"
                component={InputField}
                class="form-control"
                placeholder="Phone Number"
                validate={[
                  required(),
                  phone('Invalid Mobile'),
                  value => {
                    if (value === this.props.user.user.contact_mobile) {
                      return "You can't invite yourself";
                    }
                  },
                ]}
              />
            </div>
          )}
        </div>

        {visibleFields.role && (
          <>
            <div class="form-group Form--vertical">
              <label>Role</label>
              <div class="input-container">
                <Field name="role" component="select" class="form-control">
                  {Object.keys(ROLES).map(role => (
                    <option key={role} value={role}>
                      {ROLES[role].label}
                    </option>
                  ))}
                </Field>
              </div>
            </div>
            <div class="form-group">
              {ROLES[selectedRole] && ROLES[selectedRole].desc ? (
                <div class="alert alert-info text-center">
                  {ROLES[selectedRole].desc}
                </div>
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
    );
  }
}
