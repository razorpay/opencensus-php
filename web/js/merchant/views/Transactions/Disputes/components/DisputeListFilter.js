import ListFilter from 'merchant/components/ListFilter';
import { Field } from 'redux-form';
import { titleCase } from 'common/utils/rzp-utils';

const phases = ['retrieval', 'chargeback', 'pre_arbitration', 'arbitration', 'fraud'];

const statues = ['open', 'under_review', 'lost', 'won', 'closed'];

export default (props) => {
  return (
    <ListFilter {...props}>
      <div class="form-group list-filter-item">
        <label>Dispute Id</label>
        <Field name="id" component="input" class="form-control input-sm" />
      </div>

      <div class="form-group list-filter-item">
        <label>Payment Id</label>
        <Field name="payment_id" component="input" class="form-control input-sm" />
      </div>

      <div class="form-group list-filter-item">
        <label>Dispute Type</label>
        <Field name="phase" component="select" class="form-control input-sm">
          <option value="">All</option>
          {phases.map((phase) => (
            <option key={phase} value={phase}>
              {titleCase(phase)}
            </option>
          ))}
        </Field>
      </div>

      <div class="form-group list-filter-item">
        <label>Dispute State</label>
        <Field name="status" component="select" class="form-control input-sm">
          <option value="">All</option>
          {statues.map((status) => (
            <option key={status} value={status}>
              {titleCase(status)}
            </option>
          ))}
        </Field>
      </div>
    </ListFilter>
  );
};
