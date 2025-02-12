import { Component } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm } from 'redux-form';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import AsyncButton from 'react-async-button';
import InputField from 'common/ui/Forms/InputField';
import ModalHeader from 'common/ui/ModalHeader';
import { required, email } from 'common/utils/validators';
import { closeModal } from 'merchant_common/reducers/modals';
import { fetchUser } from 'merchant/reducers/session';
import { luminateRow } from 'merchant/reducers/app';
import { compose } from 'redux';

class CreateMerchant extends Component {
  state = {
    errors: null,
  };

  save = (props) => {
    return this.props
      .onSave(props)
      .then((referral) => {
        this.props.fetchUser();
        this.props.luminateRow(referral.id);
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
        <ModalHeader title="Create Merchant" onCloseClick={this.props.closeModal} />

        <div className="modal-body">
          <form onSubmit={handleSubmit(this.save)}>
            <div className="form-group">
              <label>Merchant Name</label>
              <Field
                name="name"
                id="name"
                component={InputField}
                className="form-control"
                validate={required()}
                placeholder="Acme Inc."
                autoFocus={true}
              />
            </div>

            <div className="form-group">
              <label>Merchant Email</label>
              <Field
                name="email"
                id="email"
                component={InputField}
                className="form-control"
                placeholder="Optional"
                validate={email('Please provide a valid email')}
              />
            </div>

            <div className="Modal__actions">
              <AsyncButton
                className="btn btn-primary btn-block"
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

export default compose(
  connect(
    (state) => {
      return {
        ...state.session,
      };
    },
    { fetchUser, luminateRow, closeModal, ...NotificationsActions },
  ),
  reduxForm({
    form: 'createMerchant',
    initialValues: {
      name: '',
      email: '',
    },
  }),
)(CreateMerchant);
