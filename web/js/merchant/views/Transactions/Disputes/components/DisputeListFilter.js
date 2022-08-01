import { useState } from 'react';
import ListFilter from 'merchant/components/ListFilter';
import DateRangePicker from 'common/ui/DateRangePicker';
import { Field } from 'redux-form';
import { titleCase } from 'common/utils/rzp-utils';
import { handleChangeTrack } from 'merchant/views/Transactions/AnalyticsTrack';

const dateRangePresets = [
  ['Past 7 Days', -7, 'days'],
  ['Past 30 Days', -30, 'days'],
  ['Past 90 Days', -90, 'days'],
];

const phases = ['retrieval', 'chargeback', 'pre_arbitration', 'arbitration', 'fraud'];

const statues = ['open', 'under_review', 'lost', 'won', 'closed'];
const track = handleChangeTrack('dispute');

export default (props) => {
  const [date, setDate] = useState({ from: '', to: '' });
  const onDatesChange = (from, to) => {
    track({ type: 'filter', args: [] });
    setDate({
      from: from.unix(),
      to: to.unix(),
    });
  };
  return (
    <ListFilter date={date} {...props}>
      <div class="form-group list-filter-item">
        <label>Dispute Id</label>
        <Field
          name="id"
          component="input"
          class="form-control input-sm"
          onChange={(...args) => {
            track({ type: 'search', args });
          }}
        />
      </div>

      <div class="form-group list-filter-item">
        <label>Payment Id</label>
        <Field
          name="payment_id"
          component="input"
          class="form-control input-sm"
          onChange={(...args) => {
            track({ type: 'search', args });
          }}
        />
      </div>

      <div className="form-group datepicker-group">
        <label>Duration</label>
        <DateRangePicker
          presets={dateRangePresets}
          defaultPreset={2}
          onDatesChange={onDatesChange}
        />
      </div>

      <div class="form-group list-filter-item">
        <label>Dispute Type</label>
        <Field
          name="phase"
          component="select"
          class="form-control input-sm"
          onChange={(...args) => {
            track({ type: 'filter', args });
          }}
        >
          <option value="">All</option>
          {phases.map((phase) => (
            <option key={phase} value={phase}>
              {titleCase(phase)}
            </option>
          ))}
        </Field>
      </div>

      <div class="form-group list-filter-item">
        <label>Dispute State</label>
        <Field
          name="status"
          component="select"
          class="form-control input-sm"
          onChange={(...args) => {
            track({ type: 'filter', args });
          }}
        >
          <option value="">All</option>
          {statues.map((status) => (
            <option key={status} value={status}>
              {titleCase(status)}
            </option>
          ))}
        </Field>
      </div>
    </ListFilter>
  );
};
