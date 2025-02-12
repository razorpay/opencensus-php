import { Field } from 'redux-form';

import ListFilter from 'merchant/components/ListFilter';
import { tokenStatuses } from '../../constants';
import { titleCase } from 'common/utils/rzp-utils';

export default function TokensListFilter(props) {
  return (
    <ListFilter {...props}>
      <div className="form-group list-filter-item">
        <label>Token Id</label>
        <Field name="id" component="input" className="form-control input-sm" />
      </div>

      <div className="form-group list-filter-item">
        <label>Payment Id</label>
        <Field name="payment_id" component="input" className="form-control input-sm" />
      </div>

      <div className="form-group list-filter-item">
        <label>Customer Contact</label>
        <Field name="customer_contact" component="input" className="form-control input-sm" />
      </div>

      <div className="form-group list-filter-item">
        <label>Status</label>
        <Field name="recurring_status" component="select" className="form-control input-sm">
          <option value="">All</option>
          {tokenStatuses.map((status) => (
            <option key={status} value={status}>
              {titleCase(status)}
            </option>
          ))}
        </Field>
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
}
