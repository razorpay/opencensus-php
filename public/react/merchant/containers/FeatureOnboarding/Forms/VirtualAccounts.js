import { Field } from 'redux-form';
import InputField from 'rzp/ui/Forms/InputField';
import AutoResizeTextarea from 'rzp/ui/Forms/AutoResizeTextarea';
import { required } from 'rzp/utils/validators';

export default () => {
  return (
    <div class="form-body">
      <div class="form-group">
        <label for="use_case" class="label-required">
          Use Case
        </label>
        <Field
          name="use_case"
          component={AutoResizeTextarea}
          rows="3"
          class="form-control"
          placeholder="Your use case for virtual accounts"
        />
      </div>

      <div class="form-group">
        <label for="expected_monthly_revenue" class="label-required">
          Expected Monthly Revenue
        </label>
        <Field
          name="expected_monthly_revenue"
          component={InputField}
          rows="2"
          class="form-control"
          placeholder="Expected monthly revenue"
          validate={[required()]}
        />
        <small class="help-block">
          <i class="icon icon-info-outline" style={{ marginRight: '4px' }} />
          <span>
            Approximate monthly revenue you expect to receive via virtual
            accounts on Razorpay Smart Collect
          </span>
        </small>
      </div>
    </div>
  );
};
