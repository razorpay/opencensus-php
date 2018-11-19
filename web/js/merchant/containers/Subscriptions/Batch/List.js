import { Component } from 'react';
import { connect } from 'react-redux';
import { Field } from 'redux-form';

import BatchList from 'merchant/containers/BatchNew/List';

import setGaTrack from 'merchant/containers/BatchNew/ga';

import { fetchHostMandateBatches as fetchAll } from 'merchant/modules/batches';
import { titleCase } from 'rzp/utils/rzp-utils';

import CreateBatch from './CreateBatch';

const gaEvents = setGaTrack('Dashboard - Subscriptions - BU');

const renderBatchOptions = openUploadModal => (
  <CreateBatch openUploadModal={openUploadModal} />
);

const typeColumn = {
  title: 'Type',
  value: ({ type }) => titleCase(type),
};

const ExtraFilterFields = () => (
  <div class="form-group list-filter-item">
    <label>Batch Type</label>
    <Field name="type" component="select" class="form-control input-sm">
      <option value="">Both</option>
      <option value="auth_link">Authorization Link</option>
      <option value="recurring_charge">Recurring Charge</option>
    </Field>
  </div>
);

@connect(null, { fetchAll })
export default class BatchListContainer extends Component {
  render() {
    return (
      <BatchList
        form="batchListFilter"
        docUrl="https://razorpay.com/docs/recurring-payments/"
        gaEvents={gaEvents}
        renderBatchOptions={renderBatchOptions}
        extraColumns={[typeColumn]}
        ExtraFilterFields={ExtraFilterFields}
        multiBatch
        {...this.props}
      />
    );
  }
}
