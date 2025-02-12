import React from 'react';
import ListFilter from 'merchant/components/ListFilter';
import { getUser } from 'merchant/store';
import { Field } from 'redux-form';

export default ({
  type,
  isPaymentlinksV2Enabled,
  isInttCurrenciesEnabled,
  trackSearchFilterForInternational,
  onSubmit,
  ...otherProps
}) => {
  const isTypeLink = type === 'link';
  const label = isTypeLink ? 'Payment Link' : 'Invoice';
  const user = getUser();

  function _onSubmit(params) {
    let trackLabel;

    switch (params.international) {
      case '0':
        trackLabel = 'Indian';
        break;
      case '1':
        trackLabel = 'International';
        break;
      default:
        trackLabel = 'All Currencies';
    }

    trackSearchFilterForInternational(trackLabel);

    return onSubmit(params);
  }

  return (
    <ListFilter {...otherProps} onSubmit={_onSubmit}>
      <div className="form-group list-filter-item">
        <label>{label} Status</label>
        <Field name="status" component="select" className="form-control input-sm">
          <option value="">All</option>
          {!isTypeLink && <option value="draft">Draft</option>}
          {isPaymentlinksV2Enabled ? (
            <option value="created">Created</option>
          ) : (
            <option value="issued">Issued</option>
          )}
          <option value="partially_paid">Partially Paid</option>
          <option value="paid">Paid</option>
          <option value="cancelled">Cancelled</option>
          <option value="expired">Expired</option>
        </Field>
      </div>

      <div className="form-group list-filter-item">
        <label>{label} Id</label>
        <Field name="id" component="input" className="form-control input-sm" />
      </div>

      {label === 'Payment Link' && (
        <div className="form-group list-filter-item">
          <label>Batch Id</label>
          <Field name="batch_id" component="input" className="form-control input-sm" />
        </div>
      )}
      <div className="form-group list-filter-item">
        <label>{isPaymentlinksV2Enabled ? 'Reference Id' : 'Receipt No.'}</label>
        <Field name="receipt" component="input" className="form-control input-sm" />
      </div>

      <div className="form-group list-filter-item">
        <label>Customer Contact</label>
        <Field name="customer_contact" component="input" className="form-control input-sm" />
      </div>

      <div className="form-group list-filter-item">
        <label>Customer Email</label>
        <Field name="customer_email" component="input" className="form-control input-sm" />
      </div>

      <div className="form-group list-filter-item">
        <label>Notes</label>
        <Field name="notes" component="input" className="form-control input-sm" />
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

      {isInttCurrenciesEnabled && user.isCountryIndia && (
        <div className="form-group list-filter-item">
          <label>Currency Type</label>
          <Field name="international" component="select" className="form-control input-sm">
            <option value="">All Currencies</option>
            <option value="0">Indian</option>
            <option value="1">International</option>
          </Field>
        </div>
      )}

      {otherProps.extraFields}
    </ListFilter>
  );
};
