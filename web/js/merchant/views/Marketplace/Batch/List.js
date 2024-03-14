import { Component } from 'react';
import { connect } from 'react-redux';
import { Field } from 'redux-form';

import BatchList from 'merchant/containers/BatchNew/ListV2';
import CreateBatch from './CreateBatch';

import setGaTrack from 'merchant/containers/BatchNew/ga';

import { fetchAllRouteBatches as fetchAll } from 'merchant/reducers/batches';
import { titleCase } from 'common/utils/rzp-utils';
import { navItems } from 'merchant/views/Marketplace/NavItems';

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
  <div class="form-group list-filter-item">
    <label>Batch Type</label>
    <Field name="type" component="select" class="form-control input-sm">
      <option value="">All</option>
      <option value="payment_transfer">Transfers</option>
      <option value="transfer_reversal">Reversals</option>
      <option value="linked_account_create">Linked accounts</option>
    </Field>
  </div>
);

@connect((state) => ({ user: state.session.user }), {
  fetchAll,
})
export default class BatchListContainer extends Component {
  render() {
    const { isPlatformFeeTabEnabled, isPartnerPlatformFeeEnabled } = this.props;
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
        propsTabData={navItems(isPlatformFeeTabEnabled, isPartnerPlatformFeeEnabled)}
        {...this.props}
      />
    );
  }
}
