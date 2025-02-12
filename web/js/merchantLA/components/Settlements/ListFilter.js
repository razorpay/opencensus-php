import { Field } from 'redux-form';
import ListFilter from 'merchant/components/ListFilter';

export default props => {
  return (
    <ListFilter {...props}>
      <div className="form-group list-filter-item">
        <label>Settlement Id</label>
        <Field name="id" component="input" className="form-control input-sm" />
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
    </ListFilter>
  );
};
