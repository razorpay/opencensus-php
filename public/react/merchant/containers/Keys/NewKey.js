import { Component, PropTypes } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm } from 'redux-form';
import InputField from 'rzp/ui/Forms/InputField';
import ModalHeader from 'rzp/ui/ModalHeader';
import Alert from 'rzp/ui/Forms/Alert';
import { isBlank } from 'rzp/utils/rzp-utils';
import { saveInvoice } from 'merchant/modules/invoices/list';
import { required, phone, email } from 'rzp/utils/validators';
import { closeModal } from 'rzp/modules/modals';

@connect(state => state.session, { closeModal })
@reduxForm({
  form: 'newKeyModal',
})
export default class NewKey extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  constructor() {
    super(...arguments);
    this.state = {
      errors: null,
    };
  }

  componentWillMount() {
    const key = this.props.apiKey;
    if (key) {
      this.props.initialize({
        keyId: key.id,
        keySecret: key.secret,
      });
    }
  }

  save = props => {
    this.context.confirm({
      message: 'Are you sure you have saved the key details? ' +
        'This is the last time we will show you the key secret.',
      affirmativeLabel: 'OK',
      action: () => this.props.closeModal(),
    });
  };

  render() {
    const { handleSubmit, apiKey } = this.props;
    const key = apiKey;

    return (
      <div>
        <ModalHeader title="New Key" onCloseClick={handleSubmit(this.save)} />

        <Alert type="error" message={this.state.errors} />

        <form
          class="form-horizontal payment-link-form"
          onSubmit={handleSubmit(this.save)}
        >
          <div class="modal-body">
            <div class="form-group">
              <label class="col-md-3 control-label">
                <div>Key Id</div>
              </label>
              <div class="col-md-8">
                <Field
                  name="keyId"
                  component="input"
                  class="form-control"
                  readOnly="readonly"
                />
              </div>
            </div>
            <div class="form-group">
              <label class="col-md-3 control-label">
                <div>Key Secret</div>
              </label>
              <div class="col-md-8">
                <Field
                  name="keySecret"
                  component="input"
                  class="form-control"
                  readOnly="readonly"
                />
              </div>
            </div>
            <div class="form-group">
              <div class="col-md-8 col-md-offset-3">
                <a href={`/keys/csv?id=${key.id}&secret=${key.secret}`}>
                  Download Key Details
                </a>
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button
              type="submit"
              class="btn btn-primary btn-rounded"
              onClick={handleSubmit(this.save)}
            >
              OK
            </button>
          </div>
        </form>
      </div>
    );
  }
}
