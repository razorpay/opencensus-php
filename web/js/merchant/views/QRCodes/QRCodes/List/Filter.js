import React from 'react';
import ListFilter from 'merchant/components/ListFilter';
import { Field } from 'redux-form';
import track from './track';

export default (props) => {
  return (
    <ListFilter {...props}>
      <div class="form-group list-filter-item">
        <label>QR Code Status</label>
        <Field name="status" component="select" class="form-control input-sm" onBlur={track.field}>
          <option value="">All</option>
          <option value="active">Active</option>
          <option value="closed">Closed</option>
        </Field>
      </div>

      <div class="form-group list-filter-item">
        <label>QR Code ID</label>
        <Field name="id" component="input" class="form-control input-sm" onBlur={track.field} />
      </div>

      <div class="form-group list-filter-item">
        <label>QR Name</label>
        <Field name="name" component="input" class="form-control input-sm" onBlur={track.field} />
      </div>

      <div class="form-group list-filter-item">
        <label>Customer Name</label>
        <Field
          name="cust_name"
          component="input"
          class="form-control input-sm"
          onBlur={track.field}
        />
      </div>

      <div class="form-group list-filter-item">
        <label>Customer Email</label>
        <Field
          name="cust_email"
          component="input"
          class="form-control input-sm"
          onBlur={track.field}
        />
      </div>

      <div class="form-group list-filter-item">
        <label>Customer Contact</label>
        <Field
          name="cust_contact"
          component="input"
          class="form-control input-sm"
          onBlur={track.field}
        />
      </div>

      <div class="form-group list-filter-item">
        <label>Notes</label>
        <Field name="notes" component="input" class="form-control input-sm" onBlur={track.field} />
      </div>
    </ListFilter>
  );
};
