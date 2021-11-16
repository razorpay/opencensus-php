import ListFilter from 'merchant/components/ListFilter';
import DateRangePicker from 'common/ui/DateRangePicker';
import { Field } from 'redux-form';
import { useState } from 'react';

const dateRangePresets = [
  ['Past 7 Days', -7, 'days'],
  ['Past 30 Days', -30, 'days'],
  ['Past 90 Days', -90, 'days'],
];

export default ({ showBatchIdFilter, ...props }) => {
  const [date, setDate] = useState({ from: '', to: '' });
  const onDatesChange = (from, to) => {
    setDate({
      from: from.unix(),
      to: to.unix(),
    });
  };

  return (
    <ListFilter date={date} {...props}>
      <div class="form-group list-filter-item">
        <label>Payment Id</label>
        <Field name="id" component="input" class="form-control input-sm" />
      </div>

      <div className="form-group datepicker-group">
        <label>Duration</label>
        {/* For m-web we want to show only one month to support mweb view */}
        <DateRangePicker presets={dateRangePresets} onDatesChange={onDatesChange} />
      </div>

      {/* used in emndate payments */}
      {showBatchIdFilter && (
        <div class="form-group list-filter-item">
          <label>Batch Id</label>
          <Field name="batch_id" component="input" class="form-control input-sm" />
        </div>
      )}

      <div class="form-group list-filter-item">
        <label>Status</label>
        <Field name="status" component="select" class="form-control input-sm">
          <option value="">All</option>
          <option value="authorized">Authorized</option>
          <option value="captured">Captured</option>
          <option value="refunded">Refunded</option>
          <option value="failed">Failed</option>
        </Field>
      </div>

      <div class="form-group list-filter-item">
        <label>Email</label>
        <Field name="email" component="input" type="email" class="form-control input-sm" />
      </div>

      <div class="form-group list-filter-item">
        <label>Notes</label>
        <Field name="notes" component="input" class="form-control input-sm" />
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

      {props.addonAfter}
    </ListFilter>
  );
};
