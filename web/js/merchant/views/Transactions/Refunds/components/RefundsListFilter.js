import React from 'react';
import ListFilter from 'merchant/components/ListFilter';
import { Field } from 'redux-form';
import { connect } from 'react-redux';
class RefundListFilter extends React.Component {
  render() {
    return (
      <ListFilter {...this.props}>
        <div class="form-group list-filter-item">
          <label>Refund Id</label>
          <Field name="id" component="input" class="form-control input-sm" />
        </div>

        <div class="form-group list-filter-item">
          <label>Payment Id</label>
          <Field name="payment_id" component="input" class="form-control input-sm" />
        </div>
        {this.props.rs_filter ? (
          <div class="form-group list-filter-item">
            <label>Status</label>
            <Field name="public_status" component="select" class="form-control input-sm">
              <option value="">All</option>
              <option value="processed">Processed</option>
              <option value="processing">Processing</option>
              <option value="failed">Failed</option>
            </Field>
          </div>
        ) : null}

        <div class="form-group list-filter-item">
          <label>Notes</label>
          <Field name="notes" component="input" class="form-control input-sm" />
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
  }
}

const mapStateToProps = (state) => {
  return {
    rs_filter: state.config.config.rs_filter,
  };
};

export default connect(mapStateToProps, null)(RefundListFilter);
