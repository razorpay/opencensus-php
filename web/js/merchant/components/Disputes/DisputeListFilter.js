import ListFilter from '../ListFilter';
import { Field } from 'redux-form';

import { snakeToTitleCase as titleCase } from 'common/util';

const phases = [
  'retrieval',
  'chargeback',
  'pre_arbitration',
  'arbitration',
  'fraud',
];

const statues = ['open', 'under_review', 'lost', 'won', 'closed'];

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
          {phases.map(phase => (
            <option key={phase} value={phase}>
              {titleCase(phase)}
            </option>
          ))}
        </Field>
      </div>

      <div class="form-group list-filter-item">
        <label>Dispute State</label>
        <Field name="status" component="select" class="form-control input-sm">
          <option value=""> </option>
          {statues.map(status => (
            <option key={status} value={status}>
              {titleCase(status)}
            </option>
          ))}
        </Field>
      </div>
    </ListFilter>
  );
};
