import { connect } from 'react-redux';
import { Field, reduxForm } from 'redux-form';
import AsyncButton from 'react-async-button';

import ModalHeader from 'common/ui/ModalHeader';
import InputField from 'common/ui/Forms/InputField';
import { required } from 'common/utils/validators';

import { showNotification } from 'merchant_common/reducers/notifications';

@connect(
  state => ({
    email: state.session.user.user.email,
  }),
  { showNotification }
)
@reduxForm({
  form: 'confirmPassword',
})
export default class PasswordVerification extends React.Component {
  onSubmit = ({ password }) => {
    return this.props.onSubmit({
      ...this.props.dataSentWithPassword,
      password,
    });
  };

  render() {
    const { email, enable, handleSubmit, closeModal } = this.props;
    return (
      <div class="2fa-modal">
        <ModalHeader
          title={(enable ? 'Enable' : 'Disable') + ' 2-step verification'}
          onCloseClick={closeModal}
        />
        <div class="modal-body">
          <p>
            To confirm please enter the password for <strong>{email}</strong>
          </p>
          <form style={{ marginBottom: '35px' }}>
            <div class="form-group">
              <Field
                type="password"
                name="password"
                component={InputField}
                class="form-control m-t"
                placeholder="Password"
                validate={[required()]}
                autoFocus
              />
            </div>
            <div class="Modal__actions">
              <AsyncButton
                class="btn btn-primary btn-block"
                text="Confirm"
                pendingText="Please Wait..."
                onClick={handleSubmit(this.onSubmit)}
              />
            </div>
          </form>
        </div>
      </div>
    );
  }
}
