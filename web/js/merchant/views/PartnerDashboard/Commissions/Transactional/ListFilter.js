import { Field } from 'redux-form';

import ListFilter from 'merchant/components/ListFilter';

import SourceTypeFilter from './SourceTypeFilter';

export default (props) => (
  <ListFilter {...props}>
    <div className="form-group list-filter-item">
      <label>Commissions ID</label>
      <Field name="id" component="input" class="form-control input-sm" />
    </div>

    <div className="form-group list-filter-item">
      <label>Merchant ID</label>
      <Field name="merchant_id" component="input" class="form-control input-sm" />
    </div>
    <div class="form-group list-filter-item">
      <label>Source</label>
      <Field name="source_type" component={SourceTypeFilter} />
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
