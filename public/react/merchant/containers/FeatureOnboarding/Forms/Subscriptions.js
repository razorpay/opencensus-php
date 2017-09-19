import { Field } from 'redux-form';
import InputField from 'rzp/ui/Forms/InputField';
import AutoResizeTextarea from 'rzp/ui/Forms/AutoResizeTextarea';
import { required } from 'rzp/utils/validators';

export default () => {
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
        <label for="email_notify">Transfer for</label>
        <Field
          name="settling_to"
          component={InputField}
          tagName="select"
          class="form-control"
          placeholder="Transferring Payments to?"
          validate={[required()]}
        >
          <option value="Businesses" key="vendors">
            Third party businesses
          </option>
          <option value="Own Accounts" key="own_accounts">
            Own bank accounts
          </option>
          <option value="Individuals" key="individuals">
            Individuals
          </option>
        </Field>
      </div>
    </div>
  );
};
