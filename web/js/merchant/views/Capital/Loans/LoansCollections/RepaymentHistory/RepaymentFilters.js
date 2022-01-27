import React from 'react';
import { Field } from 'redux-form';

import ListFilter from 'merchant/components/ListFilter';
import { REPAYMENT_FILTER_STATUS_OPTIONS } from '../../constants';

export default function RepaymentFilters({ onSubmit, repayments, ...props }) {
  return (
    <ListFilter {...props} onSubmit={onSubmit}>
      <div class="form-group list-filter-item">
        <label>Repayment ID</label>
        <Field name="reference_id" component="input" class="form-control input-sm" />
      </div>
      <div className="form-group list-filter-item">
        <label>Status</label>
        <Field name="status" component="select" class="form-control input-sm">
          <option value="">All</option>
          {Object.entries(REPAYMENT_FILTER_STATUS_OPTIONS).map(([value, label]) => (
            <option key={value} value={value}>
              {label}
            </option>
          ))}
        </Field>
      </div>

      <div className="form-group list-filter-item count">
        <label>Count</label>
        <Field
          name="count"
          component="input"
          min={10}
          max={1000}
          type="number"
          class="form-control input-sm"
        />
      </div>
    </ListFilter>
  );
}
