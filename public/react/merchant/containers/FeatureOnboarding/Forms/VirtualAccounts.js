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
          placeholder="Your use case for virtual accounts"
        />
      </div>

      <div class="form-group">
        <label for="expected_monthly_revenue">Expected Monthly Revenue</label>
        <Field
          name="expected_monthly_revenue"
          component={InputField}
          rows="2"
          class="form-control"
          placeholder="Expected monthly revenue through virtual accounts"
          validate={[required()]}
        />
      </div>
    </div>
  );
};
