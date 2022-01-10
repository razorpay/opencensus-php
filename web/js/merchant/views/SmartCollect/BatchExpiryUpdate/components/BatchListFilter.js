import ListFilter from 'merchant/components/ListFilter';
import { Field } from 'redux-form';

export default (props) => {
  return (
    <ListFilter {...props}>
      <div class="form-group list-filter-item">
        <label>Batch ID</label>
        <Field name="id" component="input" class="form-control input-sm" />
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
