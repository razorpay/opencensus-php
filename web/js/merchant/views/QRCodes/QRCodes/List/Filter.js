import React from 'react';
import ListFilter from 'merchant/components/ListFilter';
import { Field } from 'redux-form';
import track from './track';

export default (props) => {
  return (
    <ListFilter {...props}>
      <div className="form-group list-filter-item">
        <label>QR Code Status</label>
        <Field name="status" component="select" className="form-control input-sm" onBlur={track.field}>
          <option value="">All</option>
          <option value="active">Active</option>
          <option value="closed">Closed</option>
        </Field>
      </div>

      <div className="form-group list-filter-item">
        <label>QR Code ID</label>
        <Field name="id" component="input" className="form-control input-sm" onBlur={track.field} />
      </div>

      <div className="form-group list-filter-item">
        <label>QR Name</label>
        <Field name="name" component="input" className="form-control input-sm" onBlur={track.field} />
      </div>

      <div className="form-group list-filter-item">
        <label>Customer Name</label>
        <Field
          name="cust_name"
          component="input"
          className="form-control input-sm"
          onBlur={track.field}
        />
      </div>

      <div className="form-group list-filter-item">
        <label>Customer Email</label>
        <Field
          name="cust_email"
          component="input"
          className="form-control input-sm"
          onBlur={track.field}
        />
      </div>

      <div className="form-group list-filter-item">
        <label>Customer Contact</label>
        <Field
          name="cust_contact"
          component="input"
          className="form-control input-sm"
          onBlur={track.field}
        />
      </div>

      <div className="form-group list-filter-item">
        <label>Notes</label>
        <Field name="notes" component="input" className="form-control input-sm" onBlur={track.field} />
      </div>
    </ListFilter>
  );
};
