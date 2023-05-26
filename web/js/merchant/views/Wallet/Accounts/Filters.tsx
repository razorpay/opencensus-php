import { Field } from 'redux-form';
import React, { useState } from 'react';

import DateRangePicker from 'common/ui/DateRangePicker';
import ListFilter from 'merchant/components/ListFilter';
import 'react-dates/initialize';

import type { AccountListApiParams } from 'merchant/views/Wallet/types';

const dateRangePresets = [
  ['Past 7 Days', -7, 'days'],
  ['Past 30 Days', -30, 'days'],
  ['Past 90 Days', -90, 'days'],
];

interface FilterProps {
  onSubmit: (filters: AccountListApiParams) => void;
}

const Filters = ({ onSubmit }: FilterProps): JSX.Element => {
  const [date, setDate] = useState({
    from: '',
    to: '',
  });

  return (
    <ListFilter date={date} onSubmit={onSubmit} form="walletAccountsFilter">
      <div className="form-group list-filter-item">
        <label>Contact</label>
        <Field
          name="contact"
          component="input"
          class="form-control input-sm"
          data-testid="contact"
        />
      </div>

      <div className="form-group datepicker-group">
        <label>Duration</label>
        <DateRangePicker
          presets={dateRangePresets}
          onDatesChange={(start, end) => setDate({ from: start.unix(), to: end.unix() })}
          data-test-id="date"
        />
      </div>

      <div className="form-group list-filter-item">
        <label>Status</label>
        <Field name="status" component="select" class="form-control input-sm" data-testid="status">
          <option value="">All</option>
          <option value="active">Active</option>
          <option value="deactivated">Deactivated</option>
          <option value="pending_activation">Pending Activation</option>
        </Field>
      </div>
    </ListFilter>
  );
};

export default Filters;
