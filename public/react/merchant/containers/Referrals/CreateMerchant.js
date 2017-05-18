import { Component, PropTypes } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm } from 'redux-form';
import * as NotificationsActions from 'rzp/modules/notifications';
import AsyncButton from 'react-async-button';
import InputField from 'rzp/ui/Forms/InputField';
import ModalHeader from 'rzp/ui/ModalHeader';
import { required, email } from 'rzp/utils/validators';
import { closeModal } from 'rzp/modules/modals';
import { highlightReferral } from 'merchant/modules/referrals';

@connect(
  state => {
    return {
      ...state.session,
    };
  },
  { highlightReferral, closeModal, ...NotificationsActions }
)
@reduxForm({
  form: 'createMerchant',
  initialValues: {
    name: '',
    email: '',
  },
})
export default class CreateMerchant extends Component {
  state = {
    errors: null,
  };

  save = props => {
    return this.props
      .onSave(props)
      .then(referral => {
        this.props.highlightReferral(referral.id);
        this.props.showNotification({
          type: 'success',
          message: 'Merchant created',
        });
        this.props.closeModal();
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors,
        });
      });
  };

  render() {
    const { handleSubmit, referral } = this.props;

    return (
      <div>
        <ModalHeader
          title="Create Merchant"
          onCloseClick={this.props.closeModal}
        />

        <div class="modal-body">
          <form onSubmit={handleSubmit(this.save)}>
            <div class="form-group">
              <label>Merchant Name</label>
              <Field
                name="name"
                id="name"
                component={InputField}
                class="form-control"
                validate={required()}
                placeholder="Acme Inc."
                autoFocus={true}
              />
            </div>

            <div class="form-group">
              <label>Merchant Email</label>
              <Field
                name="email"
                id="email"
                component={InputField}
                class="form-control"
                placeholder="Optional"
                validate={email('Please provide a valid email')}
              />
            </div>

            <div class="Modal__actions">
              <AsyncButton
                class="btn btn-primary btn-block"
                text="Create Merchant"
                pendingText="Creating Merchant..."
                onClick={handleSubmit(this.save)}
              />
            </div>
          </form>
        </div>
      </div>
    );
  }
}
