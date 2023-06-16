import ListFilter from 'merchant/components/ListFilter';
import { Field } from 'redux-form';

export default ({ type, showAppIdFilter, showMobileNumberFilter = false, ...otherProps }) => (
  <ListFilter {...otherProps}>
    <div class="form-group list-filter-item">
      <label>Account Name</label>
      <Field name="name" component="input" class="form-control input-sm" />
    </div>

    <div class="form-group list-filter-item">
      <label>Account ID</label>
      <Field name="id" component="input" class="form-control input-sm" />
    </div>

    {showMobileNumberFilter && (
      <div class="form-group list-filter-item">
        <label>Phone Number</label>
        <Field name="contact_mobile" component="input" class="form-control input-sm" />
      </div>
    )}

    <div class="form-group list-filter-item">
      <label>Email ID</label>
      <Field name="email" component="input" class="form-control input-sm" />
    </div>

    {showAppIdFilter && (
      <div class="form-group list-filter-item">
        <label>Application Id</label>
        <Field name="application_id" component="input" class="form-control input-sm" />
      </div>
    )}

    <div class="form-group list-filter-item count">
      <label>Count</label>
      <Field
        name="count"
        component="input"
        min={1}
        max={50}
        type="number"
        class="form-control input-sm"
      />
    </div>
  </ListFilter>
);
