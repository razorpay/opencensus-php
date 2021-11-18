import { Field } from 'redux-form';

import ListFilter from 'merchant/components/ListFilter';
import { titleCase } from 'common/utils/rzp-utils';

const statuses = ['issued', 'paid', 'expired'];

export default (props) => (
  <ListFilter {...props}>
    <div class="form-group list-filter-item">
      <label>Registration Link Id</label>
      <Field name="id" component="input" class="form-control input-sm" />
    </div>

    <div class="form-group list-filter-item">
      <label>Batch ID</label>
      <Field name="batch_id" component="input" class="form-control input-sm" />
    </div>

    <div class="form-group list-filter-item">
      <label>Receipt</label>
      <Field name="receipt" component="input" class="form-control input-sm" />
    </div>

    <div class="form-group list-filter-item">
      <label>Customer Email</label>
      <Field name="customer_email" component="input" class="form-control input-sm" />
    </div>

    <div class="form-group list-filter-item">
      <label>Customer Contact</label>
      <Field name="customer_contact" component="input" class="form-control input-sm" />
    </div>

    <div class="form-group list-filter-item">
      <label>Status</label>
      <Field name="status" component="select" class="form-control input-sm">
        <option value="">All</option>
        {statuses.map((status) => (
          <option key={status} value={status}>
            {titleCase(status)}
          </option>
        ))}
      </Field>
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
