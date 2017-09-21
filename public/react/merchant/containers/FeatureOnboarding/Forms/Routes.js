import { Field } from 'redux-form';
import InputField from 'rzp/ui/Forms/InputField';
import FileUploadInputButton from 'rzp/ui/FileUpload/InputButton';
import { required } from 'rzp/utils/validators';

export default ({ handleChange }) => {
  return (
    <div class="form-body">
      <div class="form-group">
        <label for="use_case" class="label-required">
          Use Case
        </label>
        <Field
          name="use_case"
          component="textarea"
          rows="3"
          class="form-control"
          placeholder="Your use case for the product and business model"
          validate={[required()]}
        />
      </div>

      <div class="form-group">
        <label for="email_notify" class="label-required">
          Transferring To
        </label>
        <Field
          name="settling_to"
          component={InputField}
          tagName="select"
          class="form-control"
          placeholder="Transferring Payments to?"
          validate={[required()]}
        >
          <option value="Businesses" key="vendors">
            Third-party businesses
          </option>
          <option value="Own Accounts" key="own_accounts">
            Own bank accounts
          </option>
          <option value="Individuals" key="individuals">
            Individuals
          </option>
        </Field>
      </div>

      <div class="form-group">
        <label for="email_notify" class="label-required">
          Signed Vendor Agreement
        </label>
        <FileUploadInputButton
          accept="image/jpeg,image/png,application/pdf,application/x-pdf"
          uploadedFileName=""
          maxSize="8000000"
          onChange={handleChange}
        />
        <small class="help-block">
          <i class="icon icon-info-outline" style={{ marginRight: '4px' }} />
          <span>
            As a sample, upload a signed agreement executed with your
            3rd-parties or vendors
          </span>
        </small>
      </div>
    </div>
  );
};
