import React from 'react';
import { Field } from 'redux-form';
import ListFilter from 'merchant/components/ListFilter';

const OPTIONS = [
  {
    label: 'All',
    value: '',
  },
  {
    label: 'Created',
    value: 'CREATED',
  },
  {
    label: 'Initiated',
    value: 'INITIATED',
  },
  {
    label: 'Partially Processed',
    value: 'PARTIALLY_PROCESSED',
  },
  {
    label: 'Processed',
    value: 'PROCESSED',
  },
  {
    label: 'Reversed',
    value: 'REVERSED',
  },
  {
    label: 'Failed',
    value: 'FAILED',
  },
];

const RouteSettlementListFilter = (props) => {
  return (
    <ListFilter {...props}>
      <div className="form-group list-filter-item">
        <label>Settlement Id</label>
        <Field name="id" component="input" className="form-control input-sm" />
      </div>

      <div className="form-group list-filter-item">
        <label>Status</label>
        <Field name="status" component="select" className="form-control input-sm">
          {OPTIONS.map(({ label, value }) => {
            return (
              <option key={value} value={value}>
                {label}
              </option>
            );
          })}
        </Field>
      </div>

      <div className="form-group list-filter-item count">
        <label>Count</label>
        <Field
          name="count"
          component="input"
          min={1}
          max={100}
          type="number"
          className="form-control input-sm"
        />
      </div>
    </ListFilter>
  );
};

export default RouteSettlementListFilter;
