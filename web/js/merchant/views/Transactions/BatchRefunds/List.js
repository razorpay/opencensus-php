import { Component } from 'react';
import { connect } from 'react-redux';
import ListContainer from 'merchant/containers/ListContainer';
import BatchList from './components/BatchList';
import { validateRefundBatch, createRefundBatch } from 'merchant/reducers/batches';
import { fetchRefundBatches as fetchAll } from 'merchant/reducers/batches';
import setGaTrack from 'merchant/containers/BatchNew/ga';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { getCustomURL } from '../../../components/DocsLink';

const gaEvents = setGaTrack('Dashboard - Instant Refunds - BU');

@connect(
  (state) => {
    return {
      mode: state.session.mode,
      user: state.session.user,
      ...state.refundbatches,
    };
  },
  { fetchAll, validateRefundBatch, createRefundBatch },
)
export default class BatchListContainer extends ListContainer {
  render() {
    return (
      <BatchList
        form="batchListFilter"
        count={this.state.count}
        skip={this.state.skip}
        batchType="refund"
        gaEvents={gaEvents}
        paginate={this.paginate}
        onSubmit={(args) => {
          analyticsTrack({
            objectName: 'batch refunds search',
            actionName: 'clicked',
            screen: 'transactions',
            properties: {
              ...args,
              location: 'batch refunds',
              ...getCommonAnalyticsProperties(window.rzp_user),
            },
          });
          this.search(args);
        }}
        sampleUrl={SAMPLE_BATCH_REFUND_FILE_WITH_SPEED}
        docUrl={getCustomURL("https://razorpay.com/docs/payments/refunds/batch/")}
        uploadUrl="/refunds/batchupload"
        {...this.props}
      />
    );
  }
}

export const SAMPLE_BATCH_REFUND_FILE = `https://dashboard.razorpay.com/files/sample_batch_refund.xlsx`;
export const SAMPLE_BATCH_REFUND_FILE_WITH_SPEED = `https://dashboard.razorpay.com/files/sample_batch_refund_with_speed.xlsx`;
