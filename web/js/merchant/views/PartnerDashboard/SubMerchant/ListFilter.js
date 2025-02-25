import ListFilter from 'merchant/components/ListFilter';
import ActivationStatusFilter from './components/ActivationStatusFilter';
import { Field } from 'redux-form';
import React from "react";

export default ({
  showAppIdFilter,
  showContactFilter = false,
  showEmailIdFilter = true,
  showPhoneNumberFilter = true,
  showActivationStatusFilter = false,
  ...otherProps
}) => (
  <ListFilter {...otherProps}>
    <div className="form-group list-filter-item">
      <label>Account Name</label>
      <Field name="name" component="input" className="form-control input-sm" />
    </div>

    <div className="form-group list-filter-item">
      <label>Account ID</label>
      <Field name="id" component="input" className="form-control input-sm" />
    </div>

    {showPhoneNumberFilter ? (
      <div className="form-group list-filter-item">
        <label>Phone Number</label>
        <Field name="contact_mobile" component="input" className="form-control input-sm" />
      </div>
    ) : null}

    {showContactFilter ? (
      <div className="form-group list-filter-item">
        <label>Contact</label>
        <Field name="contact_info" component="input" className="form-control input-sm" />
      </div>
    ) : null}

    {showAppIdFilter ? (
      <div className="form-group list-filter-item">
        <label>Application Id</label>
        <Field name="application_id" component="input" className="form-control input-sm" />
      </div>
    ) : null}

    {showActivationStatusFilter ? (
      <div className="form-group list-filter-item">
        <label>Activation Status</label>
        <Field name="activation_status" component={ActivationStatusFilter} />
      </div>
    ) : null}

    {showEmailIdFilter ? (
      <div className="form-group list-filter-item">
        <label>Email ID</label>
        <Field name="email" component="input" className="form-control input-sm" />
      </div>
    ) : null}

    <div className="form-group list-filter-item count">
      <label>Count</label>
      <Field
        name="count"
        component="input"
        min={1}
        max={50}
        type="number"
        className="form-control input-sm"
      />
    </div>
  </ListFilter>
);
