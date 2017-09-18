import ListFilter from '../ListFilter';
import { Field } from 'redux-form';
import ShowWhen from 'merchant/components/ShowWhen';

export default ({ type, ...otherProps }) => {
  let label = type === 'link' ? 'Payment Link' : 'Invoice';

  return (
    <ListFilter {...otherProps}>
      <div class="form-group list-filter-item">
        <label>
          {label} Status
        </label>
        <Field name="status" component="select" class="form-control input-sm">
          <option value="">All</option>
          <option value="draft">Draft</option>
          <option value="issued">Issued</option>
          <ShowWhen featureEnabled="Invoice_Partial_Payments">
            <option value="partially_paid">Partially Paid</option>
          </ShowWhen>
          <option value="paid">Paid</option>
          <option value="cancelled">Cancelled</option>
          <option value="expired">Expired</option>
        </Field>
      </div>

      <div class="form-group list-filter-item">
        <label>
          {label} Id
        </label>
        <Field name="id" component="input" class="form-control input-sm" />
      </div>

      <div class="form-group list-filter-item">
        <label>Receipt No.</label>
        <Field name="receipt" component="input" class="form-control input-sm" />
      </div>

      <div class="form-group list-filter-item">
        <label>Customer Contact</label>
        <Field
          name="customer_contact"
          component="input"
          class="form-control input-sm"
        />
      </div>

      <div class="form-group list-filter-item">
        <label>Customer Email</label>
        <Field
          name="customer_email"
          component="input"
          class="form-control input-sm"
        />
      </div>

      <div class="form-group list-filter-item">
        <label>Notes</label>
        <Field name="notes" component="input" class="form-control input-sm" />
      </div>

      <div class="form-group list-filter-item count">
        <label>Count</label>
        <Field
          name="count"
          component="input"
          min={1}
          max={100}
          type="number"
          class="form-control input-sm"
        />
      </div>
    </ListFilter>
  );
};
