// components
import { useState } from 'react';
import ListFilter from 'merchant/components/ListFilter';
import DateRangePicker from 'common/ui/DateRangePicker';
import { Field } from 'redux-form';

// constants
const dateRangePresets = [
  ['Past 7 Days', -7, 'days'],
  ['Past 30 Days', -30, 'days'],
  ['Past 90 Days', -90, 'days'],
];

const B2bPaymentListFilter = (props) => {
  const [date, setDate] = useState({ from: '', to: '' });
  const onDatesChange = (from, to) => {
    setDate({
      from: from.unix(),
      to: to.unix(),
    });
  };

  const [provider, setProvider] = useState({ name: 'All', value: '', gateway: '' });

  return (
    <ListFilter date={date} provider={provider} setProvider={setProvider} {...props}>
      <div className="form-group list-filter-item">
        <label>Payment Id</label>
        <Field name="id" component="input" className="form-control input-sm" />
      </div>

      <div className="form-group datepicker-group">
        <label>Duration</label>
        {/* For m-web we want to show only one month to support mweb view */}
        <DateRangePicker presets={dateRangePresets} onDatesChange={onDatesChange} />
      </div>

      <div className="form-group list-filter-item">
        <label>Status</label>
        <Field name="status" component="select" className="form-control input-sm">
          <option value="">All</option>
          <option value="authorized">Authorized</option>
          <option value="captured">Captured</option>
          <option value="refunded">Refunded</option>
          <option value="failed">Failed</option>
        </Field>
      </div>

      <div className="form-group list-filter-item count">
        <label>Count</label>
        <Field
          name="count"
          component="input"
          min={1}
          max={100}
          type="number"
          className="form-control input-sm"
        />
      </div>
    </ListFilter>
  );
};

export default B2bPaymentListFilter;
