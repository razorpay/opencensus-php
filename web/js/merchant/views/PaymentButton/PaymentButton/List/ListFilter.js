import { Field } from 'redux-form';
import ListFilter from 'merchant/components/ListFilter';
import track from './track';

export default (props) => {
  return (
    <ListFilter {...props}>
      <div className="form-group list-filter-item">
        <label>Title</label>
        <Field
          name="title"
          component="input"
          className="form-control input-sm"
          onBlur={track.searchTitle}
        />
      </div>

      <div className="form-group list-filter-item">
        <label>Status</label>
        <Field name="status" component="select" className="form-control input-sm">
          <option value="">All</option>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
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
          onBlur={track.searchCount}
        />
      </div>
    </ListFilter>
  );
};
