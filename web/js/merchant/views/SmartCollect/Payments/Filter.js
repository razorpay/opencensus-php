import ListFilter from 'merchant/components/ListFilter';
import { Field } from 'redux-form';

export default ({ ...props }) => {
  return (
    <ListFilter {...props}>
      <div className="form-group list-filter-item">
        <label>Payment Id</label>
        <Field
          name="id"
          component="input"
          className="form-control input-sm"
          onBlur={props.onEleBlur('payment_id')}
        />
      </div>
      <div className="form-group list-filter-item">
        <label>Customer Identifier Id</label>
        <Field
          name="virtual_account_id"
          component="input"
          className="form-control input-sm"
          onBlur={props.onEleBlur('virtual_account_id')}
        />
      </div>
      <div className="form-group list-filter-item">
        <label>Payment Status</label>
        <Field
          name="status"
          component="select"
          className="form-control input-sm"
          onBlur={props.onEleBlur('status')}
        >
          <option value="">All</option>
          <option value="authorized">Authorized</option>
          <option value="captured">Captured</option>
          <option value="refunded">Refunded</option>
          <option value="failed">Failed</option>
        </Field>
      </div>
      <div className="form-group list-filter-item">
        <label>Email</label>
        <Field
          name="email"
          component="input"
          type="email"
          className="form-control input-sm"
          onBlur={props.onEleBlur('email')}
        />
      </div>
      <div className="form-group list-filter-item">
        <label>Bank Reference Number</label>
        <Field
          name="va_transaction_id"
          component="input"
          className="form-control input-sm"
          onBlur={props.onEleBlur('va_transaction_id')}
        />
      </div>
      <div className="form-group list-filter-item">
        <label>Notes</label>
        <Field
          name="notes"
          component="input"
          className="form-control input-sm"
          onBlur={props.onEleBlur('notes')}
        />
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
          onBlur={props.onEleBlur('count')}
        />
      </div>
    </ListFilter>
  );
};
