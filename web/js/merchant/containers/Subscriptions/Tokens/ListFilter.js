import { Field } from 'redux-form';

import ListFilter from 'merchant/components/ListFilter';

export default props => (
  <ListFilter {...props}>
    <div class="form-group list-filter-item">
      <label>Token Id</label>
      <Field name="id" component="input" class="form-control input-sm" />
    </div>

    <div class="form-group list-filter-item">
      <label>Batch Id</label>
      <Field name="batch_id" component="input" class="form-control input-sm" />
    </div>

    <div class="form-group list-filter-item">
      <label>Email</label>
      <Field
        name="customer_email"
        component="input"
        class="form-control input-sm"
      />
    </div>

    <div class="form-group list-filter-item">
      <label>Contact</label>
      <Field
        name="customer_contact"
        component="input"
        class="form-control input-sm"
      />
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
