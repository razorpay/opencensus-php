import { Field } from 'redux-form';
import React, { useState } from 'react';

import DateRangePicker from 'common/ui/DateRangePicker';
import ListFilter from 'merchant/components/ListFilter';
import 'react-dates/initialize';

import type { AccountLoadsListApiParams } from 'merchant/views/Wallet/types';

const dateRangePresets = [
  ['Past 7 Days', -7, 'days'],
  ['Past 30 Days', -30, 'days'],
  ['Past 90 Days', -90, 'days'],
];

interface FilterProps {
  onSubmit: (filters: AccountLoadsListApiParams) => void;
}

const Filters = ({ onSubmit }: FilterProps): JSX.Element => {
  const [date, setDate] = useState({
    from: '',
    to: '',
  });

  return (
    <ListFilter date={date} onSubmit={onSubmit} form="walletAccountsLoadFilter">
      <div className="form-group list-filter-item">
        <label>Load Id</label>
        <Field
          name="load_id"
          component="input"
          class="form-control input-sm"
          data-testid="loadId"
        />
      </div>

      <div className="form-group list-filter-item">
        <label>Account Id</label>
        <Field
          name="issuing_account_id"
          component="input"
          class="form-control input-sm"
          data-testid="accountId"
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
    </ListFilter>
  );
};

export default Filters;
