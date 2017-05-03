import { Component, PropTypes } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm, formValueSelector } from 'redux-form';
import * as NotificationsActions from 'rzp/modules/notifications';
import AsyncButton from 'react-async-button';
import InputField from 'rzp/ui/Forms/InputField';
import ModalHeader from 'rzp/ui/ModalHeader';
import Alert from 'rzp/ui/Forms/Alert';
import Amount from 'rzp/ui/Amount';
import { required, email } from 'rzp/utils/validators';
import { isBlank } from 'rzp/utils/rzp-utils';
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
})
export default class CreateMerchant extends Component {
  constructor() {
    super(...arguments);
    this.state = {
      errors: null,
    };
  }

  componentWillMount() {
    let referral = this.props.referral;
    this.props.initialize({
      name: '',
      email: '',
    });
  }

  save = props => {
    return this.props
      .onSave(props)
      .then(referral => {
        this.props.highlightReferral(referral.id);
        this.props.showNotification({
          type: 'success',
          message: 'Merchant created',
          closeTimeout: 5000,
        });
        this.props.closeModal();
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors,
          closeTimeout: 5000,
        });
      });
  };

  render() {
    const { handleSubmit, referral } = this.props;

    return (
      <div>
        <ModalHeader
          title="Create Login"
          onCloseClick={this.props.closeModal}
        />

        <form class="form-horizontal" onSubmit={handleSubmit(this.save)}>
          <div class="modal-body">
            <div class="form-group">
              <label class="col-md-3 control-label">
                <div>Merchant Name</div>
              </label>
              <div class="col-md-8">
                <Field
                  name="name"
                  id="name"
                  component={InputField}
                  class="form-control"
                  validate={required()}
                  placeholder="Acme Inc."
                />
              </div>
            </div>
            <div class="form-group">
              <label class="col-md-3 control-label">
                <div>Merchant Email</div>
              </label>
              <div class="col-md-8">
                <Field
                  name="email"
                  id="email"
                  component={InputField}
                  class="form-control"
                  placeholder="Optional"
                  validate={email('Please provide a valid email')}
                />
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button
              type="button"
              class="btn btn-default btn-rounded"
              onClick={this.props.closeModal}
            >
              Cancel
            </button>

            <AsyncButton
              type="submit"
              class="btn btn-primary btn-rounded"
              text="Create Login"
              pendingText="Creating Login..."
              onClick={handleSubmit(this.save)}
            />
          </div>
        </form>
      </div>
    );
  }
}
