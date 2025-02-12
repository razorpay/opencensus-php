import { Field } from 'redux-form';
import React from 'react';

import ListFilter from 'merchant/components/ListFilter';

import type { AccountListApiParams } from 'merchant/views/Wallet/types';

interface FilterProps {
  onSubmit: (filters: AccountListApiParams) => void;
}

const Filters = ({ onSubmit }: FilterProps): JSX.Element => {
  return (
    <ListFilter onSubmit={onSubmit} form="walletAccountsFilter">
      <div className="form-group list-filter-item">
        <label>Account Id</label>
        <Field
          name="issuing_account_id"
          component="input"
          className="form-control input-sm"
          data-testid="account_id"
        />
      </div>

      <div className="form-group list-filter-item">
        <label>User Id</label>
        <Field
          name="user_id"
          component="input"
          className="form-control input-sm"
          data-testid="user_id"
        />
      </div>

      <div className="form-group list-filter-item">
        <label>Contact</label>
        <Field
          name="contact"
          component="input"
          className="form-control input-sm"
          data-testid="contact"
        />
      </div>

      <div className="form-group list-filter-item">
        <label>Email Id</label>
        <Field name="email" component="input" className="form-control input-sm" data-testid="email" />
      </div>

      <div className="form-group list-filter-item">
        <label>Type</label>
        <Field name="type" component="select" className="form-control input-sm" data-testid="type">
          <option value="">All</option>
          <option value="container">Container</option>
          <option value="giftcard">Gift Card</option>
          <option value="wallet">Account</option>
        </Field>
      </div>

      <div className="form-group list-filter-item">
        <label>Status</label>
        <Field name="status" component="select" className="form-control input-sm" data-testid="status">
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
