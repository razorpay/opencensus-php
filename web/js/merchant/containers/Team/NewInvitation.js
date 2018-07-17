import { Component } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm, formValueSelector } from 'redux-form';
import AsyncButton from 'react-async-button';
import InputField from 'rzp/ui/Forms/InputField';
import { required, email } from 'rzp/utils/validators';
import { roles } from 'rzp/utils/constants';
import { without } from 'rzp/utils/rzp-utils';
import { sendInvitation, fetchTeamDetails } from 'merchant/modules/team';
import * as NotificationsActions from 'rzp/modules/notifications';

const ROLES = without(roles, 'owner');
const selector = formValueSelector('newInvitation');
@connect(
  state => {
    return {
      selectedRole: selector(state, 'role'),
      ...state.session,
    };
  },
  {
    sendInvitation,
    fetchTeamDetails,
    ...NotificationsActions,
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
  save = props => {
    let user = this.props.user.user;

    return this.props
      .sendInvitation({ ...props, sender_name: user.name })
      .then(() => {
        this.props.fetchTeamDetails({ merchant_id: this.props.user.current });
        this.props.initialize(this.props.initialValues);
        this.props.showNotification({
          type: 'success',
          message: `Invitation has been successfully sent to ${props.email}`,
        });
      })
      .catch(err => {
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });
      });
  };

  render() {
    const { handleSubmit, selectedRole } = this.props;

    return (
      <form onSubmit={handleSubmit(this.save)} style={{ marginBottom: '35px' }}>
        <div class="row">
          <div class="col-md-5">
            <div class="form-group">
              <Field
                name="email"
                component={InputField}
                class="form-control"
                placeholder="Email address of the user"
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
          </div>

          <div class="col-md-4">
            <div class="form-group">
              <Field name="role" component="select" class="form-control">
                {Object.keys(ROLES).map(role => (
                  <option key={role} value={role}>
                    {ROLES[role].label}
                  </option>
                ))}
              </Field>
            </div>
          </div>

          <div class="col-md-3">
            <div class="form-group">
              <AsyncButton
                class="btn btn-primary"
                text="Send Invitation"
                pendingText="Sending Invitation..."
                onClick={handleSubmit(this.save)}
              />
            </div>
          </div>
        </div>

        <div class="form-group">
          {ROLES[selectedRole] && ROLES[selectedRole].desc ? (
            <div class="alert alert-info text-center">
              {ROLES[selectedRole].desc}
            </div>
          ) : null}
        </div>
      </form>
    );
  }
}
