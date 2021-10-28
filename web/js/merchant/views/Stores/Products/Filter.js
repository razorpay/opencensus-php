import React from 'react';
import ListFilter from 'merchant/components/ListFilter';
import { Field } from 'redux-form';

export default (props) => {
  return (
    <ListFilter {...props}>
      <div class="form-group list-filter-item">
        <label>Product Name</label>
        <Field name="name" component="input" class="form-control input-sm" />
      </div>

      <div class="form-group list-filter-item">
        <label>Status</label>
        <Field name="status" component="select" class="form-control input-sm">
          <option value="">All</option>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </Field>
      </div>
    </ListFilter>
  );
};
