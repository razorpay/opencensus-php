import ListFilter from 'merchant/components/ListFilter';
import { Field } from 'redux-form';

export default ({ showBatchIdFilter, ...props }) => {
  return (
    <ListFilter {...props} hideClear>
      <div className="form-group list-filter-item">
        <label>Payment Id</label>
        <Field name="id" component="input" className="form-control input-sm" />
      </div>

      <div className="form-group list-filter-item">
        <label>Status</label>
        <Field name="status" component="select" className="form-control input-sm">
          <option value="">All</option>
          <option value="authorized">Authorized</option>
          <option value="captured">Captured</option>
          <option value="refunded">Refunded</option>
          <option value="failed">Failed</option>
        </Field>
      </div>

      <div className="form-group list-filter-item">
        <label>Email</label>
        <Field name="email" component="input" type="email" className="form-control input-sm" />
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
