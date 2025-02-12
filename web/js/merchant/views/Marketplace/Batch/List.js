import { Component } from 'react';
import { connect } from 'react-redux';
import { Field } from 'redux-form';

import { titleCase } from 'common/utils/rzp-utils';
import BatchList from 'merchant/containers/BatchNew/ListV2';
import setGaTrack from 'merchant/containers/BatchNew/ga';
import { fetchAllRouteBatches as fetchAll } from 'merchant/reducers/batches';
import { navItems } from 'merchant/views/Marketplace/NavItems';

import CreateBatch from './CreateBatch';

const typesLabelMap = {
  payment_transfer: 'Transfers',
  linked_account_create: 'Linked Accounts',
  transfer_reversal: 'Reversals',
};

const typeColumn = {
  title: 'Type',
  value: ({ type }) => typesLabelMap[type] || titleCase(type),
};

const renderBatchOptions = (openUploadModal) => <CreateBatch openUploadModal={openUploadModal} />;

const gaEvents = setGaTrack('Dashboard - Route - BU');

const emptyResultsDescription =
  'Create multiple Transfers, Reversals or linked Accounts, in one go using a batch file. Simply upload a file containing all the information.';

const BatchTypeFilterField = () => (
  <div className="form-group list-filter-item">
    <label>Batch Type</label>
    <Field name="type" component="select" className="form-control input-sm">
      <option value="">All</option>
      <option value="payment_transfer">Transfers</option>
      <option value="transfer_reversal">Reversals</option>
      <option value="linked_account_create">Linked accounts</option>
    </Field>
  </div>
);

class BatchListContainer extends Component {
  render() {
    const { isPlatformFeeTabEnabled, user, isPartnerPlatformFeeEnabled } = this.props;
    return (
      <BatchList
        form="batchListFilter"
        docUrl="https://razorpay.com/docs/route/dashboard/batch-upload/"
        gaEvents={gaEvents}
        ExtraFilterFields={BatchTypeFilterField}
        renderBatchOptions={renderBatchOptions}
        extraColumns={[typeColumn]}
        multiBatch
        emptyResultsDescription={emptyResultsDescription}
        propsTabData={navItems(user, isPlatformFeeTabEnabled, isPartnerPlatformFeeEnabled)}
        {...this.props}
      />
    );
  }
}

export default connect((state) => ({ user: state.session.user }), {
  fetchAll,
})(BatchListContainer);
