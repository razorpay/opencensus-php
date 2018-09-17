import { Component } from 'react';
import { connect } from 'react-redux';
import Button from 'component/Button';
import * as ModalActions from 'rzp/modules/modals';
import ModalHeader from 'rzp/ui/ModalHeader';
import InputField from 'rzp/ui/Forms/InputField';
import { Field, reduxForm } from 'redux-form';
import RadioButton from 'rzp/ui/Forms/RadioButton';
import Popover, { PopoverBody } from 'rzp/ui/Popover';

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

    this.props.initialValues.email = this.props.user.user.email;
    this.props.initialValues.phone = this.props.user.contact_mobile;
    this.state = {
      saving: false,
      saved: false,
    };
    this.onSubmit = this.onSubmit.bind(this);
  }

  onSubmit(body) {
    this.setState({
      saving: true,
    });
    axios({
      method: 'post',
      url: 'https://hooks.zapier.com/hooks/catch/1088429/qljsgo',
      data: {
        user_email: body.email,
        user_phone: body.phone,
        merchant_id: this.props.user.current,
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
        if (this.props.closeBanner) {
          this.props.closeBanner();
        }
        this.setState({
          saving: false,
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
            <div>
              <Field
                name="interested_in"
                component={RadioButton}
                htmlValue="automatic"
                label={() => (
                  <span>
                    Automatic Early Settlements
                    <span class="help-content">
                      <i className="i i-help m-l" />
                      <Popover
                        align="right"
                        theme="dark"
                        parentQuerySelector=".rzp-early-stl-modal"
                      >
                        <PopoverBody>
                          All of your settlements are done early, within few
                          hours.
                        </PopoverBody>
                      </Popover>
                    </span>
                  </span>
                )}
              />
            </div>

            <Field
              name="interested_in"
              component={RadioButton}
              htmlValue="on-demand"
              label={() => (
                <span>
                  On-demand Early Settlements
                  <span class="help-content">
                    <i className="i i-help m-l" />
                    <Popover
                      align="right"
                      theme="dark"
                      parentQuerySelector=".rzp-early-stl-modal"
                    >
                      <PopoverBody>
                        Settle your balance amount when needed. The Settlement
                        will be initiated in the next available slot.
                      </PopoverBody>
                    </Popover>
                  </span>
                </span>
              )}
            />
          </div>
          <Button.Primary class="submit-btn" disabled={this.state.saving}>
            Request Early Access
          </Button.Primary>
        </form>
      </div>
    );
  }
}
