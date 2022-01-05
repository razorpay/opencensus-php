import { Field } from 'redux-form';
import ListFilter from 'merchant/components/ListFilter';

import { PowerSelect } from 'react-power-select';
import { humanize } from 'common/utils/rzp-utils';
import moment from 'moment';

const statusList = [
  'created',
  'authenticated',
  'active',
  'pending',
  'halted',
  'cancelled',
  'completed',
  'expired',
  'paused',
];

const next7DaysEpoch = moment().add(7, 'days').unix();
const next30DaysEpoch = moment().add(30, 'days').unix();
const next60DaysEpoch = moment().add(60, 'days').unix();

export default function SubscriptionsListFilter(props) {
  return (
    <ListFilter {...props} maxMwebFiltersLength={3}>
      <div class="form-group list-filter-item">
        <label>Customer Email</label>
        <Field
          name="customer_email"
          component="input"
          class="form-control input-sm"
          onChange={props.onFieldChange}
        />
      </div>

      <div class="form-group list-filter-item">
        <label>Cards Expiring In</label>
        <Field
          name="token_expire_before"
          component="select"
          class="form-control input-sm"
          onChange={props.onFieldChange}
        >
          <option value="" />
          <option value={next7DaysEpoch}>Next 7 days</option>
          <option value={next30DaysEpoch}>Next 30 days</option>
          <option value={next60DaysEpoch}>Next 60 days</option>
        </Field>
      </div>

      {props.showSubscriptionExpiryFilter && (
        <div class="form-group list-filter-item">
          <label>Subscriptions Completing In</label>
          <Field
            name="complete_before"
            component="select"
            class="form-control input-sm"
            onChange={props.onFieldChange}
          >
            <option value="" />
            <option value={next7DaysEpoch}>Next 7 days</option>
            <option value={next30DaysEpoch}>Next 30 days</option>
            <option value={next60DaysEpoch}>Next 60 days</option>
          </Field>
        </div>
      )}

      <div class="form-group list-filter-item">
        <label>Subscription Id</label>
        <Field
          name="id"
          component="input"
          class="form-control input-sm"
          onChange={props.onFieldChange}
        />
      </div>

      <div class="form-group list-filter-item">
        <label>Plan ID</label>
        <Field
          name="plan_id"
          component="input"
          class="form-control input-sm"
          onChange={props.onFieldChange}
        />
      </div>

      <div class="form-group list-filter-item">
        <label>Status</label>
        <Field
          name="status"
          component={(renderProps) => (
            <PowerSelect
              options={statusList}
              selected={renderProps.input.value}
              showClear={false}
              class="custom-powerselect"
              selectedOptionComponent={({ option }) => <div>{humanize(option)}</div>}
              optionComponent={({ option }) => (
                <div class="custom-powerselect-options">{humanize(option)}</div>
              )}
              searchEnabled={false}
              onChange={({ option }) => {
                renderProps.input.onChange(option);
                props.onFieldChange();
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
          onChange={props.onFieldChange}
        />
      </div>
    </ListFilter>
  );
}
