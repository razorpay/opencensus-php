import ListFilter from 'merchant/components/ListFilter';
import { Field } from 'redux-form';
import { handleChangeTrack } from 'merchant/views/Transactions/AnalyticsTrack';

const track = handleChangeTrack('batchRefund');

export default (props) => {
  return (
    <ListFilter {...props}>
      <div class="form-group list-filter-item">
        <label>Batch Upload Id</label>
        <Field
          name="id"
          component="input"
          class="form-control input-sm"
          onChange={(...args) => {
            track({ type: 'search', args });
          }}
        />
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
