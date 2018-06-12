import { Component } from 'react';
import { Field } from 'redux-form';
import InputField from 'rzp/ui/Forms/InputField';
import { required } from 'rzp/utils/validators';
import { lenientUrl } from 'rzp/utils/validators';

export default class SubscriptionsPreStep extends Component {
  static title = 'Website/App details';

  render() {
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
            validate={[required(), lenientUrl]}
          />
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
      </div>
    );
  }
}
