import { Field } from 'redux-form';
import React, { useState } from 'react';

import DateRangePicker from 'common/ui/DateRangePicker';
import ListFilter from 'merchant/components/ListFilter';
import { ContactField, ReferenceIdField } from 'merchant/views/Wallet/common/Fields';

import type { TransactionFilterParams } from 'merchant/views/Wallet/types';
import 'react-dates/initialize';

const dateRangePresets = [
  ['Past 7 Days', -7, 'days'],
  ['Past 30 Days', -30, 'days'],
  ['Past 90 Days', -90, 'days'],
];

interface FilterProps {
  onSubmit: (filters: TransactionFilterParams) => void;
}

const Filters = ({ onSubmit }: FilterProps): JSX.Element => {
  const [date, setDate] = useState({
    from: '',
    to: '',
  });

  return (
    <ListFilter date={date} onSubmit={onSubmit} form="walletAccountsFilter">
      <div className="form-group list-filter-item">
        <label>Transaction Id</label>
        <Field
          name="transaction_id"
          component="input"
          className="form-control input-sm"
          data-testid="id"
        />
      </div>

      <ReferenceIdField />

      <ContactField />

      <div className="form-group datepicker-group">
        <label>Duration</label>
        <DateRangePicker
          presets={dateRangePresets}
          onDatesChange={(start, end) => setDate({ from: start.unix(), to: end.unix() })}
        />
      </div>
    </ListFilter>
  );
};

export default Filters;
