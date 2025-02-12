import { Field } from 'redux-form';
import React, { useState } from 'react';

import DateRangePicker from 'common/ui/DateRangePicker';
import ListFilter from 'merchant/components/ListFilter';
import { ContactField, ReferenceIdField } from 'merchant/views/Wallet/common/Fields';

import 'react-dates/initialize';

import type { AccountPaymentsListApiParams } from 'merchant/views/Wallet/types';

const dateRangePresets = [
  ['Past 7 Days', -7, 'days'],
  ['Past 30 Days', -30, 'days'],
  ['Past 90 Days', -90, 'days'],
];

interface FilterProps {
  onSubmit: (filters: AccountPaymentsListApiParams) => void;
}

const Filters = ({ onSubmit }: FilterProps): JSX.Element => {
  const [date, setDate] = useState({
    from: '',
    to: '',
  });

  return (
    <ListFilter date={date} onSubmit={onSubmit} form="walletAccountsPaymentsFilter">
      <div className="form-group list-filter-item">
        <label>Payment Id</label>
        <Field name="id" component="input" className="form-control input-sm" data-testid="paymentId" />
      </div>

      <div className="form-group list-filter-item">
        <label>Account Id</label>
        <Field
          name="account_id"
          component="input"
          className="form-control input-sm"
          data-testid="accountId"
        />
      </div>

      <ReferenceIdField />

      <ContactField />

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
