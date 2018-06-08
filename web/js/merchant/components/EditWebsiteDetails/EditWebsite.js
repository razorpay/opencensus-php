import { Component } from 'react';
import { reduxForm } from 'redux-form';
import { Field } from 'redux-form';

import InputField from 'rzp/ui/Forms/InputField';
import { required, lenientUrl } from 'rzp/utils/validators';

export default reduxForm({ form: 'editWebsiteDetails' })(
  ({ onSubmit, onCancel, handleSubmit }) => {
    return (
      <form
        className={`edit-website-details-form${
          onCancel ? ' has-cancel-button' : ''
        }`}
        onSubmit={handleSubmit(onSubmit)}
      >
        <div className="form-group">
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
        </div>
        <div className="form-group">
          <label>Website/App Link</label>
          <Field
            name="business_website"
            component={InputField}
            class="form-control"
            validate={[required(), lenientUrl('Please enter a valid URL')]}
          />
        </div>
        <div className="form-group">
          <button type="button" className="btn btn-default" onClick={onCancel}>
            Cancel
          </button>
          <button className="btn btn-primary">Add Details</button>
        </div>
      </form>
    );
  }
);

export const SuccessModalContent = ({ onClose }) => (
  <div>
    <div className="form-group">
      Thank you for providing your website/app details. We will review the
      required details and get back to you soon. In case of any queries, we’ll
      contact you via mail.
    </div>
    <div className="form-group">
      <button
        className="btn btn-primary btn-block center-block"
        style={{ width: '50%' }}
        onClick={onClose}
      >
        Okay, Got it!
      </button>
    </div>
  </div>
);
