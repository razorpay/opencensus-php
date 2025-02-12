import ListFilter from 'merchant/components//ListFilter';
import { Field } from 'redux-form';

export default props => {
  return (
    <ListFilter {...props}>
      <div className="form-group list-filter-item">
        <label>Transfer Id</label>
        <Field name="id" component="input" className="form-control input-sm" />
      </div>

      {props.isShowParentPaymentIdEnabled && (
        <div className="form-group list-filter-item">
          <label>Parent Payment Id</label>
          <Field
            name="parent_payment_id"
            component="input"
            className="form-control input-sm"
          />
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
      <div className="form-group list-filter-item">
        <label>Notes</label>
        <Field name="notes" component="input" className="form-control input-sm" />
      </div>
    </ListFilter>
  );
};
