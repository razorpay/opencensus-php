import { Field } from 'redux-form';
import ListFilter from 'merchant/components/ListFilter';

export default (props) => {
  return (
    <ListFilter {...props}>
      <div class="form-group list-filter-item">
        <label>Virtual Account Id</label>
        <Field
          name="id"
          component="input"
          class="form-control input-sm"
          onBlur={props.onEleBlur('va_id')}
        />
      </div>

      <div class="form-group list-filter-item">
        <label>Notes</label>
        <Field
          name="notes"
          class="form-control input-sm"
          component="input"
          onBlur={props.onEleBlur('notes')}
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
          onBlur={props.onEleBlur('count')}
        />
      </div>
    </ListFilter>
  );
};
