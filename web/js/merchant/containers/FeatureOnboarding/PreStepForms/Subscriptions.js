import { Component } from 'react';
import { connect } from 'react-redux';
import { reduxForm } from 'redux-form';
import { Field } from 'redux-form';

import { required } from 'rzp/utils/validators';
import { lenientUrl } from 'rzp/utils/validators';
import { updateSession } from 'merchant/modules/session';
import { merchantFetch } from 'rzp/utils/ajax';
import { showNotification } from 'rzp/modules/notifications';

import InputField from 'rzp/ui/Forms/InputField';
import AsyncButton from 'react-async-button';
import User from 'merchant/models/User';

@connect(
  state => ({
    user: state.session.user,
    mode: state.session.mode,
    initialValues: {
      business_website: state.session.user.business_website,
    },
  }),
  {
    updateSession,
    showNotification,
  }
)
@reduxForm({
  form: 'editWebsiteDetails_subscription',
})
export default class SubscriptionsPreStep extends Component {
  handleSave = form => {
    const { user, mode } = this.props;

    return merchantFetch({
      url: 'merchant/activation/update_website_details',
      mode: 'live',
      method: 'put',
      data: { business_website: form.business_website },
    })
      .then(response => {
        if (response.success) {
          //update user session details
          const newUser = new User({
            ...user,
            business_website: response.data.business_website,
          });

          this.props.updateSession({
            user: newUser,
            mode,
          });
          this.props.showNotification({
            type: 'success',
            message: 'Website details update successfully.',
          });
        }
      })
      .catch(err => {
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });
      });
  };

  render() {
    const { disabled } = this.props;
    return (
      <div class="form-body">
        <div class="form-group">
          <label for="business_website" class="label-required">
            Website/App Link
          </label>
          <Field
            name="business_website"
            component={InputField}
            rows="3"
            class="form-control"
            placeholder="https://razorpay.com"
            validate={[required(), lenientUrl('Invalid url')]}
          />
          {/* disabled={disabled} */}
          <small class="help-block">
            <span className="text-muted">
              Your website/app should contain these pages:{' '}
            </span>
            <span class="text-links">
              <strong>
                About Us, Contact Us,{' '}
                <a
                  class="btn-link"
                  href="https://docs.google.com/document/d/1yqqWTE_jfC8F_u9UV9nLq3AUZR2wwpQGJigRJV3YQvg/pub"
                  target="_blank"
                >
                  Privacy Policy
                </a>,{' '}
                <a
                  class="btn-link"
                  href="https://docs.google.com/document/d/1bCwt0WccF7oDMBGAGRxtPgUfzqGzkUjtLnnE1JlL2dg/pub"
                  target="_blank"
                >
                  Terms & Conditions
                </a>,{' '}
                <a
                  class="btn-link"
                  href="https://docs.google.com/document/d/1xYM1QHm9S5phnkzyENqJ3KXv37schlsiTp0Id_4IMwE/pub"
                  target="_blank"
                >
                  Cancellation/Refund Policies
                </a>
              </strong>.
            </span>
          </small>
        </div>
        <div class="form-group">
          <AsyncButton
            type="button"
            class="btn btn-primary"
            text="Next Step"
            pendingText="Saving..."
            onClick={this.props.handleSubmit(this.handleSave)}
          />
          {/* disabled={disabled} */}
        </div>
      </div>
    );
  }
}
