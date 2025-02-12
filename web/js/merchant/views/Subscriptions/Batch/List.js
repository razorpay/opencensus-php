import { Component } from 'react';
import { connect } from 'react-redux';
import { Field } from 'redux-form';

import BatchList from 'merchant/containers/BatchNew/List';
import setGaTrack from 'merchant/containers/BatchNew/ga';
import {
  fetchHostMandateBatches as fetchAll,
  fetchHostMandateAuthLinkBatches,
} from 'merchant/reducers/batches';

import CreateBatch from './CreateBatch';

const gaEvents = setGaTrack('Dashboard - Subscriptions - BU');

const renderBatchOptions = (openUploadModal) => <CreateBatch openUploadModal={openUploadModal} />;

const LINK_TYPE_MAP = {
  auth_link: 'Registration Link',
  recurring_charge: 'Recurring Charge',
  recurring_charge_bulk: 'Recurring Charge',
};

const typeColumn = {
  title: 'Type',
  value: ({ type }) => LINK_TYPE_MAP[type],
};

const BatchTypeFilterField = () => (
  <div className="form-group list-filter-item">
    <label>Batch Type</label>
    <Field name="type" component="select" className="form-control input-sm">
      <option value="">Both</option>
      <option value="auth_link">Registration Link</option>
      <option value="recurring_charge">Recurring Charge</option>
    </Field>
  </div>
);

class BatchListContainer extends Component {
  fetchAll = (filter) => {
    if (this.props.user.isRegistrationLinkSupervisorRole) {
      return this.props.fetchHostMandateAuthLinkBatches(filter);
    }

    return this.props.fetchAll(filter);
  };
  render() {
    const ExtraFilterFields = !this.props.user.isRegistrationLinkSupervisorRole && {
      ExtraFilterFields: BatchTypeFilterField,
    };

    return (
      <BatchList
        form="batchListFilter"
        docUrl="https://razorpay.com/docs/recurring-payments/dashboard-operations/batch-operations/"
        gaEvents={gaEvents}
        renderBatchOptions={renderBatchOptions}
        extraColumns={[typeColumn]}
        multiBatch
        {...this.props}
        fetchAll={this.fetchAll}
        {...ExtraFilterFields}
      />
    );
  }
}

export default connect((state) => ({ user: state.session.user }), {
  fetchAll,
  fetchHostMandateAuthLinkBatches,
})(BatchListContainer);
