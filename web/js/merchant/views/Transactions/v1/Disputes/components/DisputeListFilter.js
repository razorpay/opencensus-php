import { useState } from 'react';
import moment from 'moment';
import { withRouter } from 'react-router-dom';
import { Field } from 'redux-form';

import { useSplitzService } from 'common/splitz';
import DateRangePicker from 'common/ui/DateRangePicker';
import { titleCase, humanize } from 'common/utils/rzp-utils';
import ListFilter from 'merchant/components/ListFilter';
import { handleChangeTrack } from 'merchant/views/Transactions/v1/AnalyticsTrack';
import { ALL_LABEL } from 'merchant/views/Transactions/v2/common/constants';
import {
  trackDurationFilter,
  trackStatusFilter,
} from 'merchant/views/Transactions/v2/common/tracking';
import { isTransactionsV2Enabled } from 'merchant/views/Transactions/v2/common/utils';

const dateRangePresets = [
  ['Past 7 Days', -7, 'days'],
  ['Past 30 Days', -30, 'days'],
  ['Past 90 Days', -90, 'days'],
];

const phases = ['retrieval', 'chargeback', 'pre_arbitration', 'arbitration', 'fraud'];

const statues = ['open', 'under_review', 'lost', 'won', 'closed'];
const track = handleChangeTrack('dispute');

const DisputesListFilter = (props) => {
  const {
    location: { pathname },
    user,
  } = props;
  const splitz = useSplitzService();
  const [date, setDate] = useState({ from: '', to: '' });
  const onDatesChange = (from, to) => {
    const version = isTransactionsV2Enabled(splitz, user) ? 'v2' : undefined;
    const dateRange = `${moment(from).format('DD MMM YYYY')} to ${moment(to).format(
      'DD MMM YYYY',
    )}`;
    track({ type: 'filter', args: [], splitz });
    if (version) {
      trackDurationFilter({
        dateRange,
        pathname,
      });
    }
    setDate({
      from: from.unix(),
      to: to.unix(),
    });
  };
  return (
    <ListFilter date={date} {...props}>
      <div class="form-group list-filter-item">
        <label>Dispute Id</label>
        <Field name="id" component="input" class="form-control input-sm" />
      </div>

      <div class="form-group list-filter-item">
        <label>Payment Id</label>
        <Field name="payment_id" component="input" class="form-control input-sm" />
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
            trackStatusFilter({ status: humanize(args[1]) || ALL_LABEL, pathname });
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

export default withRouter(DisputesListFilter);
