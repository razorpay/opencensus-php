import React from 'react';
import { Field } from 'redux-form';
import ListFilter from 'merchant/components/ListFilter';
import trackIS from 'merchant/views/Settlements/InstantSettlements/ga';

const InstantSettlementListFilter = (props) => {
  return (
    <ListFilter {...props}>
      <div className="form-group list-filter-item">
        <label>Settlement Id</label>
        <Field name="id" component="input" className="form-control input-sm" />
      </div>

      <div className="form-group list-filter-item">
        <label>Status</label>
        <Field
          name="status"
          component="select"
          className="form-control input-sm"
          onClick={() => trackIS.filterISStatus()}
        >
          <option value="">All</option>
          <option value="created">Created</option>
          <option value="initiated">Initiated</option>
          <option value="partially_processed">Partially Processed</option>
          <option value="processed">Processed</option>
          <option value="reversed">Reversed</option>
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
          onClick={() => trackIS.filterISCount()}
        />
      </div>
    </ListFilter>
  );
};

export default InstantSettlementListFilter;
