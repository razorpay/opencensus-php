import ListFilter from 'merchant/components/ListFilter';
import { Field } from 'redux-form';

export default ({ type, ...otherProps }) => (
  <ListFilter {...otherProps}>
    <div class="form-group list-filter-item">
      <label>Merchant Name</label>
      <Field name="name" component="input" class="form-control input-sm" />
    </div>

    <div class="form-group list-filter-item">
      <label>Merchant ID</label>
      <Field name="id" component="input" class="form-control input-sm" />
    </div>

    <div class="form-group list-filter-item">
      <label>Mail ID</label>
      <Field name="email" component="input" class="form-control input-sm" />
    </div>

    <div class="form-group list-filter-item">
      <label>Application Id</label>
      <Field
        name="application_id"
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
