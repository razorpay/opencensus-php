import { Field } from 'redux-form';
import InputField from 'rzp/ui/Forms/InputField';
import AutoResizeTextarea from 'rzp/ui/Forms/AutoResizeTextarea';
import CheckboxField from 'rzp/ui/Forms/CheckboxField';
import { required } from 'rzp/utils/validators';

export default () => {
  return (
    <div class="form-body">
      <div class="form-group">
        <label for="business_model" class="label-required">
          Use Case and Business Model
        </label>
        <Field
          name="business_model"
          component={AutoResizeTextarea}
          rows="3"
          class="form-control"
          placeholder="Your use case for Subscriptions and business model"
        />
      </div>

      <div class="form-group">
        <label for="sample_plans" class="label-required">
          Subscription Plans
        </label>
        <Field
          name="sample_plans"
          component={AutoResizeTextarea}
          rows="3"
          class="form-control"
          placeholder="Define a few sample plans that you offer"
        />
      </div>

      <div class="form-group">
        <label
          for="website_checkbox"
          class="label-required"
          class="label-required"
        >
          Is your website live?
        </label>
        <div class="checkbox">
          <label class="i-switch">
            <Field name="website_checkbox" component={CheckboxField} />
            <i />
          </label>
        </div>
      </div>

      <div class="form-group">
        <label for="website_details">Link to your plans page</label>
        <Field
          name="website_details"
          component={InputField}
          class="form-control"
          placeholder="http://example.com/pricing"
          validate={[required()]}
        />
      </div>
    </div>
  );
};
