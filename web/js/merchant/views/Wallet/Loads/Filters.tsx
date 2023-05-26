import { Field } from 'redux-form';
import React from 'react';

import ListFilter from 'merchant/components/ListFilter';
import 'react-dates/initialize';

import type { AccountLoadsListApiParams } from 'merchant/views/Wallet/types';

interface FilterProps {
  onSubmit: (filters: AccountLoadsListApiParams) => void;
}

const Filters = ({ onSubmit }: FilterProps): JSX.Element => {
  return (
    <ListFilter onSubmit={onSubmit} form="walletAccountsLoadFilter">
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
