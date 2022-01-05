import { Field } from 'redux-form';

import ListFilter from 'merchant/components/ListFilter';

export default function TokensListFilter(props) {
  return (
    <ListFilter {...props}>
      <div class="form-group list-filter-item">
        <label>Token Id</label>
        <Field name="id" component="input" class="form-control input-sm" />
      </div>

      <div class="form-group list-filter-item">
        <label>Payment Id</label>
        <Field name="payment_id" component="input" class="form-control input-sm" />
      </div>

      <div class="form-group list-filter-item">
        <label>Customer Contact</label>
        <Field name="customer_contact" component="input" class="form-control input-sm" />
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
}
