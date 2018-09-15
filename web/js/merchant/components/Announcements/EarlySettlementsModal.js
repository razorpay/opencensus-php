import { Component } from 'react';
import { connect } from 'react-redux';
import Button from 'component/Button';
import * as ModalActions from 'rzp/modules/modals';
import ModalHeader from 'rzp/ui/ModalHeader';
import InputField from 'rzp/ui/Forms/InputField';
import { Field, reduxForm } from 'redux-form';
import RadioButton from 'rzp/ui/Forms/RadioButton';

@connect(
  state => ({ user: state.session.user }),
  { ...ModalActions }
)
@reduxForm({
  form: 'es-access',
  initialValues: {
    email: '',
    phone: '',
    interested_in: 'automatic',
  },
})
export default class RequestEarlyAccessForm extends Component {
  constructor(props) {
    super(props);

    this.props.initialValues.email = this.props.user.email;
    this.props.initialValues.phone = this.props.user.contact_mobile;
    this.state = {};
    this.state.saved = false;
    this.onSubmit = this.onSubmit.bind(this);
  }

  onSubmit(body) {
    axios({
      method: 'post',
      url: 'https://hooks.zapier.com/hooks/catch/1088429/qljsgo',
      data: {
        user_email: body.email,
        user_phone: body.phone,
        merchant_id: this.props.user.id,
        merchant_name: this.props.user.name,
        role: this.props.user.role,
        activation_status: this.props.user.activation_status,
        interested_in: body.interested_in,
      },
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
    }).then(response => {
      if (response.status == 200) {
        this.props.closeBanner();
        this.setState({
          saved: true,
        });
      }
    });
  }

  render() {
    let { handleSubmit } = this.props;

    return this.state.saved ? (
      <div class="modal-body rzp-early-stl-modal">
        <ModalHeader
          title="Request Sent"
          onCloseClick={this.props.closeModal}
        />
        <div class="help-block">
          Thanks for your interest. We will get in touch with you soon.
        </div>
        <Button.Primary class="close-btn" onClick={this.props.closeModal}>
          Close
        </Button.Primary>
      </div>
    ) : (
      <div class="modal-body rzp-early-stl-modal">
        <ModalHeader
          title="Request Early Settlements"
          onCloseClick={this.props.closeModal}
        />
        <div class="help-block">
          With early settlements, you will receive your settlements ahead of
          your schedule. Share your contact details and we will get back to you
          with details.
        </div>
        <form onSubmit={handleSubmit(this.onSubmit)}>
          <div class="form-group">
            <label>Contact Email</label>
            <div>
              <Field
                name="email"
                type="email"
                component={InputField}
                class="form-control"
              />
            </div>
          </div>
          <div class="form-group">
            <label>Contact Phone</label>
            <div>
              <Field name="phone" component={InputField} class="form-control" />
            </div>
          </div>
          <div class="form-group">
            <label>Interested In</label>
            <Field
              name="interested_in"
              component={RadioButton}
              htmlValue="automatic"
              label="Automatic Early Settlements"
            />
            <Field
              name="interested_in"
              component={RadioButton}
              htmlValue="on-demand"
              label="On-demand Early Settlements"
            />
          </div>
          <Button.Primary class="submit-btn">
            Request Early Access
          </Button.Primary>
        </form>
      </div>
    );
  }
}
