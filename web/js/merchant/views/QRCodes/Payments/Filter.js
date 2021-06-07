import ListFilter from 'merchant/components/ListFilter';
import { Field } from 'redux-form';

export default (props) => {
  return (
    <ListFilter {...props}>
      <div class="form-group list-filter-item">
        <label>QR Code Id</label>
        <Field name="qr_code_id" component="input" class="form-control input-sm" />
      </div>

      <div class="form-group list-filter-item">
        <label>Payment Id</label>
        <Field name="payment_id" component="input" class="form-control input-sm" />
      </div>

      <div class="form-group list-filter-item">
        <label>Payment Status</label>
        <Field name="status" component="select" class="form-control input-sm">
          <option value="">All</option>
          <option value="authorized">Authorized</option>
          <option value="captured">Captured</option>
          <option value="refunded">Refunded</option>
          <option value="failed">Failed</option>
        </Field>
      </div>

      <div class="form-group list-filter-item">
        <label>Email</label>
        <Field name="email" component="input" type="email" class="form-control input-sm" />
      </div>

      <div class="form-group list-filter-item">
        <label>Bank Reference Number</label>
        <Field name="provider_reference_id" component="input" class="form-control input-sm" />
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
