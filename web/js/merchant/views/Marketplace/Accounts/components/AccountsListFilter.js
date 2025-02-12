import ListFilter from 'merchant/components/ListFilter';
import { Field } from 'redux-form';

export default (props) => {
  return (
    <ListFilter {...props}>
      <div className="form-group list-filter-item">
        <label>Account Id</label>
        <Field name="id" component="input" className="form-control input-sm" />
      </div>

      <div className="form-group list-filter-item">
        <label>Account Email</label>
        <Field name="email" component="input" className="form-control input-sm" />
      </div>

      {props.isRouteCodeSupportEnabled && (
        <div className="form-group list-filter-item">
          <label>Account Code</label>
          <Field name="code" component="input" className="form-control input-sm" />
        </div>
      )}

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
