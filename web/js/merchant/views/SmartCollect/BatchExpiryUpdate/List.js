import { connect } from 'react-redux';
import ListContainer from 'merchant/containers/ListContainer';
import { withRouter } from 'common/deprecated/withRouter';
import BatchList from './components/BatchList';
import {
  validateVABatch,
  createVABatch,
  fetchVABatches as fetchAll,
} from 'merchant/reducers/batches';
import { bindActionCreators } from 'redux';

class BatchListContainer extends ListContainer {
  render() {
    return (
      <BatchList
        form="batchListFilter"
        count={this.state.count}
        skip={this.state.skip}
        batchType="virtual_account_edit"
        paginate={this.paginate}
        onSubmit={(args) => {
          this.search(args);
        }}
        sampleUrl="/files/sample_batch_va_expiry_update.xlsx"
        docUrl="https://razorpay.com/docs/payments/smart-collect/update-expiry/bulk/"
        {...this.props}
      />
    );
  }
}

const mapStateToProps = (state) => {
  return { mode: state.session.mode, user: state.session.user, ...state.virtualAccountBatches };
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators({ fetchAll, validateVABatch, createVABatch }, dispatch);

export default connect(mapStateToProps, mapDispatchToProps)(withRouter(BatchListContainer));
