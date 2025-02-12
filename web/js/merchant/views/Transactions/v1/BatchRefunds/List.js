import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { withSplitzService } from 'common/splitz';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { getCustomURL } from 'merchant/components/DocsLink';
import setGaTrack from 'merchant/containers/BatchNew/ga';
import ListContainer from 'merchant/containers/ListContainer';
import { withRouter } from 'common/deprecated/withRouter';
import {
  validateRefundBatch,
  createRefundBatch,
  fetchRefundBatches as fetchAll,
} from 'merchant/reducers/batches';
import {
  selfServerTrack,
  selfServeTrackResult,
} from 'merchant/views/Transactions/v1/AnalyticsTrack';

import BatchList from './components/BatchList';

const gaEvents = setGaTrack('Dashboard - Instant Refunds - BU');

export const SAMPLE_BATCH_REFUND_FILE = `https://dashboard.razorpay.com/files/sample_batch_refund.xlsx`;
export const SAMPLE_BATCH_REFUND_FILE_WITH_SPEED = `https://dashboard.razorpay.com/files/sample_batch_refund_with_speed.xlsx`;

class BatchListContainer extends ListContainer {
  render() {
    const { splitz } = this.props;
    const version = 'v2';
    return (
      <BatchList
        form="batchListFilter"
        count={this.state.count}
        skip={this.state.skip}
        batchType="refund"
        gaEvents={gaEvents}
        paginate={this.paginate}
        onSubmit={(args) => {
          selfServerTrack({ type: 'batchRefund', actionType: 'search', splitz });
          analyticsTrack({
            objectName: 'batch refunds search',
            actionName: 'clicked',
            screen: 'transactions',
            properties: {
              ...args,
              location: 'batch refunds',
              version,
              ...getCommonAnalyticsProperties(window.rzp_user),
            },
          });

          this.search(args).then(() => {
            selfServeTrackResult({ type: 'batchRefund', actionType: 'search', splitz });
          });
        }}
        sampleUrl={SAMPLE_BATCH_REFUND_FILE_WITH_SPEED}
        docUrl={getCustomURL('https://razorpay.com/docs/payments/refunds/batch/')}
        uploadUrl="/refunds/batchupload"
        version={version}
        {...this.props}
      />
    );
  }
}

const mapStateToProps = (state) => {
  return { mode: state.session.mode, user: state.session.user, ...state.refundbatches };
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators({ fetchAll, validateRefundBatch, createRefundBatch }, dispatch);

export default withSplitzService(
  connect(mapStateToProps, mapDispatchToProps)(withRouter(BatchListContainer)),
);
