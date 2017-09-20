import { Field } from 'redux-form';
import InputField from 'rzp/ui/Forms/InputField';
import AutoResizeTextarea from 'rzp/ui/Forms/AutoResizeTextarea';
import FileUploadInputButton from 'rzp/ui/FileUpload/InputButton';
import { required } from 'rzp/utils/validators';

export default ({ handleChange }) => {
  return (
    <div class="form-body">
      <div class="form-group">
        <label for="use_case">Use Case</label>
        <Field
          name="use_case"
          component={AutoResizeTextarea}
          rows="3"
          class="form-control"
          placeholder="Your use case for the product and business model"
        />
      </div>

      <div class="form-group">
        <label for="email_notify">Transferring To</label>
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
        <label for="email_notify">Signed Vendor Agreement</label>
        <FileUploadInputButton
          accept="image/jpeg,image/png,application/pdf,application/x-pdf"
          uploadedFileName="marketplace.vendor_agreement"
          maxSize="8000000"
          onChange={handleChange}
        />
        <small class="help-block">
          <i class="icon icon-info-circle" />
          <span>
            As a sample, upload a signed agreement executed with your
            3rd-parties or vendors
          </span>
        </small>
      </div>
    </div>
  );
};
