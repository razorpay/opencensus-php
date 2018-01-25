import ListFilter from '../ListFilter';
import { Field } from 'redux-form';

export default ({ type, ...otherProps }) => {
  return (
    <ListFilter {...otherProps}>
      <div class="form-group list-filter-item">
        <label>Search</label>
        <Field
          name="id"
          component="input"
          class="form-control input-sm"
          placeholder="Payment Id or Dispute Id"
        />
      </div>

      <div class="form-group list-filter-item">
        <label>Dispute Type</label>
        <Field name="phase" component="select" class="form-control input-sm">
          <option value=""> </option>
          <option value="retrieval">Retrieval</option>
          <option value="chargeback">Chargeback</option>
          <option value="pre_arbitration">Pre Arbitration</option>
          <option value="arbitration">Arbitration</option>
          <option value="fraud">Fraud</option>
        </Field>
      </div>

      <div class="form-group list-filter-item">
        <label>Dsipute State</label>
        <Field name="status" component="select" class="form-control input-sm">
          <option value=""> </option>
          <option value="open">Open</option>
          <option value="under_review">Under Review</option>
          <option value="lost">Lost</option>
          <option value="won">Won</option>
          <option value="closed">Closed</option>
        </Field>
      </div>
    </ListFilter>
  );
};
