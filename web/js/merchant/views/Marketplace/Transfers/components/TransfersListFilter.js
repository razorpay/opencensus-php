import ListFilter from 'merchant/components/ListFilter';
import { Field } from 'redux-form';

export default (props) => {
  return (
    <ListFilter {...props}>
      <div className="form-group list-filter-item">
        <label>Transfer Id</label>
        <Field name="id" component="input" className="form-control input-sm" />
      </div>
      <div className="form-group list-filter-item">
        <label>Transfer Status</label>
        <Field name="status" component="select" className="form-control input-sm">
          <option value="">All</option>
          <option value="created">Created</option>
          <option value="pending">Pending</option>
          <option value="processed">Processed</option>
          <option value="failed">Failed</option>
          <option value="reversed">Reversed</option>
          <option value="partially_reversed">Partially Reversed</option>
        </Field>
      </div>

      <div className="form-group list-filter-item">
        <label>Settlement Status</label>
        <Field name="settlement_status" component="select" className="form-control input-sm">
          <option value="">All</option>
          <option value="pending">Pending</option>
          <option value="settled">Settled</option>
          <option value="on_hold">On Hold</option>
        </Field>
      </div>

      <div className="form-group list-filter-item">
        <label>Recipient Id</label>
        <Field name="recipient" component="input" className="form-control input-sm" />
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

      {/* <div className="form-group list-filter-item">
        <label>Notes</label>
        <Field name="notes" component="input" className="form-control input-sm" />
      </div> */}
    </ListFilter>
  );
};
