import ListFilter from '../ListFilter';
import { Field } from 'redux-form';

export default ({
  ExtraFilterFields = () => null,
  onBatchSearchCountChange = () => {},
  ...props
}) => {
  return (
    <ListFilter {...props}>
      <div className="form-group list-filter-item">
        <label>Batch Upload Id</label>
        <Field
          name="id"
          component="input"
          className="form-control input-sm"
          onBlur={props.onBatchIdChange}
        />
      </div>
      <ExtraFilterFields />
      <div className="form-group list-filter-item count">
        <label>Count</label>
        <Field
          name="count"
          component="input"
          min={1}
          max={100}
          type="number"
          className="form-control input-sm"
          onBlur={onBatchSearchCountChange}
        />
      </div>
    </ListFilter>
  );
};
