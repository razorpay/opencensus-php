import { reduxForm, Field } from 'redux-form';
import AsyncButton from 'react-async-button';

import ShowWhen from 'merchant/components/ShowWhen';
import InputField from 'common/ui/Forms/InputField';
import { required, lenientUrl } from 'common/utils/validators';

const EditWebsite = reduxForm({ form: 'editWebsiteDetails' })(
  ({ onSubmit, onCancel, handleSubmit }) => {
    return (
      <form class={`edit-website-details-form${onCancel ? ' has-cancel-button' : ''}`}>
        <div class="form-group">
          <span class="text-muted">
            Your website/app should contain these pages:{' '}
            <ShowWhen
              additionalCondition={(user) => user.isOrgAllowedFunctionality('external_links')}
            >
              <strong>
                About Us, Contact Us,{' '}
                <a
                  class="btn-link"
                  href="https://docs.google.com/document/d/1yqqWTE_jfC8F_u9UV9nLq3AUZR2wwpQGJigRJV3YQvg/pub"
                  target="_blank"
                  rel="noreferrer noopener"
                >
                  Privacy Policy
                </a>
                ,{' '}
                <a
                  class="btn-link"
                  href="https://docs.google.com/document/d/1bCwt0WccF7oDMBGAGRxtPgUfzqGzkUjtLnnE1JlL2dg/pub"
                  target="_blank"
                  rel="noreferrer noopener"
                >
                  Terms & Conditions
                </a>
                ,{' '}
                <a
                  class="btn-link"
                  href="https://docs.google.com/document/d/1xYM1QHm9S5phnkzyENqJ3KXv37schlsiTp0Id_4IMwE/pub"
                  target="_blank"
                  rel="noreferrer noopener"
                >
                  Cancellation/Refund Policies
                </a>
              </strong>
              .
            </ShowWhen>
            <ShowWhen
              additionalCondition={(user) => !user.isOrgAllowedFunctionality('external_links')}
            >
              <strong>Privacy Policy, Terms & Conditions, Cancellation/Refund Policies.</strong>
            </ShowWhen>
          </span>
        </div>
        <div class="form-group">
          <label>Website/App Link</label>
          <Field
            name="business_website"
            component={InputField}
            class="form-control"
            validate={[required(), lenientUrl('Please enter a valid URL')]}
          />
        </div>
        <div class="form-group">
          <button type="button" class="btn btn-default" onClick={onCancel}>
            Cancel
          </button>
          <AsyncButton
            class="btn btn-primary"
            text="Add Details"
            onClick={handleSubmit(onSubmit)}
            pendingText="Please Wait..."
          />
        </div>
      </form>
    );
  },
);

export default EditWebsite;
