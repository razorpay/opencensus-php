import { Field } from 'redux-form';
import ListFilter from '../ListFilter';

import { PowerSelect } from 'react-power-select';
import { humanize } from 'rzp/utils/rzp-utils';

const statusList = [
  'created',
  'authenticated',
  'active',
  'pending',
  'halted',
  'cancelled',
  'completed',
  'expired',
];

export default props => {
  return (
    <ListFilter {...props}>
      <div class="form-group list-filter-item">
        <label>Subscription Id</label>
        <Field name="id" component="input" class="form-control input-sm" />
      </div>

      <div class="form-group list-filter-item">
        <label>Plan ID</label>
        <Field
          name="plan_id"
          component="input"
          class="form-control input-sm"
          placeholder="Enter plan id"
        />
      </div>

      <div class="form-group list-filter-item">
        <label>Customer Email</label>
        <Field
          name="customer_email"
          component="input"
          class="form-control input-sm"
        />
      </div>

      <div class="form-group list-filter-item">
        <label>Status</label>
        <Field
          name="status"
          component={props => (
            <PowerSelect
              options={statusList}
              selected={props.input.value}
              showClear={false}
              className="custom-powerselect"
              selectedOptionComponent={({ option }) => (
                <div>{humanize(option)}</div>
              )}
              optionComponent={({ option }) => (
                <div className="custom-powerselect-options">
                  {humanize(option)}
                </div>
              )}
              searchEnabled={false}
              onChange={({ option }) => {
                props.input.onChange(option);
              }}
            />
          )}
        />
      </div>

      <div class="form-group list-filter-item count">
        <label>Count</label>
        <Field
          name="count"
          component="input"
          min={1}
          max={100}
          type="number"
          class="form-control input-sm"
        />
      </div>
    </ListFilter>
  );
};
