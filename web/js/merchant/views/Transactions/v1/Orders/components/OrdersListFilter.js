import ListFilter from 'merchant/components/ListFilter';
import { Field } from 'redux-form';
import { handleChangeTrack } from 'merchant/views/Transactions/v1/AnalyticsTrack';
import { trackStatusFilter } from 'merchant/views/Transactions/v2/common/tracking';
import { withRouter } from 'react-router-dom';
import { humanize } from 'common/utils/rzp-utils';
import { ALL_LABEL } from 'merchant/views/Transactions/v2/common/constants';

const track = handleChangeTrack('order');

const OrdersListFilter = (props) => {
  const {
    location: { pathname },
  } = props;
  return (
    <ListFilter {...props}>
      <div class="form-group list-filter-item">
        <label>Order Id</label>
        <Field name="id" component="input" class="form-control input-sm" />
      </div>

      <div class="form-group list-filter-item">
        <label>Receipt</label>
        <Field name="receipt" component="input" class="form-control input-sm" />
      </div>

      <div class="form-group list-filter-item">
        <label>Notes</label>
        <Field
          name="notes"
          component="input"
          class="form-control input-sm"
          onChange={(...args) => {
            track({ type: 'search', args });
          }}
        />
      </div>

      <div class="form-group list-filter-item">
        <label>Status</label>
        <Field
          name="status"
          component="select"
          class="form-control input-sm"
          onChange={(...args) => {
            track({ type: 'filter', args });
            trackStatusFilter({ status: humanize(args[1]) || ALL_LABEL, pathname });
          }}
        >
          <option value="">All</option>
          <option value="created">Created</option>
          <option value="attempted">Attempted</option>
          <option value="paid">Paid</option>
        </Field>
      </div>

      <div class="form-group list-filter-item count">
        <label>Count</label>
        <Field
          name="count"
          component="input"
          min={1}
          max={100}
          type="number"
          class="form-control input-sm"
        />
      </div>
    </ListFilter>
  );
};

export default withRouter(OrdersListFilter);
