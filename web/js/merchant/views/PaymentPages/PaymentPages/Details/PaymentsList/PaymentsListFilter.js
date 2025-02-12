import ListFilter from 'merchant/components/ListFilter';
import { Field } from 'redux-form';

import track from '../track';

export default ({ showBatchIdFilter, ...props }) => {
  return (
    <ListFilter {...props} hideClear onSubmit={track.search}>
      <div className="form-group list-filter-item">
        <label>Payment Id</label>
        <Field
          name="id"
          component="input"
          className="form-control input-sm"
          onBlur={track.searchPaymentId}
        />
      </div>

      {/* used in emndate payments */}
      {showBatchIdFilter && (
        <div className="form-group list-filter-item">
          <label>Batch Id</label>
          <Field name="batch_id" component="input" className="form-control input-sm" />
        </div>
      )}

      <div className="form-group list-filter-item">
        <label>Status</label>
        <Field
          name="status"
          component="select"
          className="form-control input-sm"
          onChange={track.searchStatus}
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
          onBlur={track.searchEmail}
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
          onBlur={track.searchCount}
        />
      </div>
    </ListFilter>
  );
};
