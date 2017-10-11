import { Component, PropTypes } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm } from 'redux-form';
import AsyncButton from 'react-async-button';
import InputField from 'rzp/ui/Forms/InputField';
import ModalHeader from 'rzp/ui/ModalHeader';
import Alert from 'rzp/ui/Forms/Alert';
import { isBlank } from 'rzp/utils/rzp-utils';
import { generateKey } from 'merchant/modules/keys';
import { required, phone, email } from 'rzp/utils/validators';
import { closeModal } from 'rzp/modules/modals';
import RadioButton from 'rzp/ui/Forms/RadioButton';

@connect(state => state.session, { closeModal })
@reduxForm({
  form: 'rollKey',
  initialValues: {
    delay_roll: '1',
  },
})
export default class RollKey extends Component {
  constructor() {
    super(...arguments);
    this.state = {
      errors: null,
    };
  }

  save = props => {
    var params = this.props.params;
    params.delay_roll = props.delay_roll;
    params.merchantId = this.props.merchantId;

    return this.props.generateKey(params).catch(({ errors }) => {
      this.setState({
        errors,
      });
    });
  };

  render() {
    const { handleSubmit } = this.props;

    return (
      <div>
        <ModalHeader title="Roll Key" onCloseClick={this.props.closeModal} />

        <form class="form-horizontal" onSubmit={handleSubmit(this.save)}>
          <div class="modal-body">
            <Alert type="error" message={this.state.errors} />

            <div class="radio rollkey">
              <Field
                component={RadioButton}
                name="delay_roll"
                htmlValue="0"
                label={() => <span>De-activate Old Key Immediately</span>}
              />
            </div>
            <div class="radio">
              <Field
                component={RadioButton}
                name="delay_roll"
                htmlValue="1"
                label={() => <span>De-activate old key in 24 hours</span>}
              />
            </div>
          </div>

          <div class="modal-footer">
            <button
              type="button"
              class="btn btn-default"
              onClick={this.props.closeModal}
            >
              Cancel
            </button>

            <AsyncButton
              type="submit"
              class="btn btn-primary"
              text="OK"
              onClick={handleSubmit(this.save)}
            />
          </div>
        </form>
      </div>
    );
  }
}
