import { Field } from 'redux-form';
import React from 'react';

import ListFilter from 'merchant/components/ListFilter';
import 'react-dates/initialize';

import type { AccountPaymentsListApiParams } from 'merchant/views/Wallet/types';

interface FilterProps {
  onSubmit: (filters: AccountPaymentsListApiParams) => void;
}

const Filters = ({ onSubmit }: FilterProps): JSX.Element => {
  return (
    <ListFilter onSubmit={onSubmit} form="walletAccountsPaymentsFilter">
      <div className="form-group list-filter-item">
        <label>Account Id</label>
        <Field
          name="accountId"
          component="input"
          class="form-control input-sm"
          data-testid="accountId"
        />
      </div>
    </ListFilter>
  );
};

export default Filters;
