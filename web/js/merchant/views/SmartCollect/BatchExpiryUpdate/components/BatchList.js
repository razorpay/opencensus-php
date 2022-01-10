import { Component } from 'react';
import { connect } from 'react-redux';
import DataTable from 'common/ui/Table/DataTable';
import HeaderAction from 'common/ui/HeaderAction';
import { batchIdLink, batchName, totalCount, status, createdAt } from 'common/ui/item/pair';
import { batchDownload } from 'merchant/reducers/batches';
import { showNotification } from 'merchant_common/reducers/notifications';
import BatchListFilter from './BatchListFilter';
import { openModal } from 'merchant_common/reducers/modals';
import BatchUpload from 'merchant/containers/BatchNew/Upload';
import { bindActionCreators } from 'redux';
import setGaTrack from 'merchant/containers/BatchNew/ga';
const gaEvents = setGaTrack('Dashboard - Batch Expiry Update - BU');

function batchActions({ onDownloadClick }) {
  return {
    title: 'Actions',
    value: (item) => (
      <div class="btn-toolbar">
        <button class="btn btn-xs btn-default" onClick={() => onDownloadClick(item.id)}>
          Download Report
        </button>
      </div>
    ),
  };
}
class BatchList extends Component {
  download = (id) => {
    const windowRef = window.open('', '_blank');
    this.props
      .batchDownload(id)
      .then((response) => {
        windowRef.location.href = response.data.url;
      })
      .catch(({ errors }) => {
        windowRef.close();
        this.props.showNotification({
          type: 'error',
          message: errors,
        });
      });
  };

  openBatchUploadModal = () => {
    this.props.openModal({
      size: 'large',
      component: (
        <BatchUpload
          docUrl={this.props.docUrl}
          sampleUrl={this.props.sampleUrl}
          closeUrl="/smartcollect/batchuploads"
          ctaText="Create Batch"
          pendingText="Creating & Sending..."
          batchType={this.props.batchType}
          maxRows={10000}
          createBatch={this.props.createVABatch}
          validateBatch={this.props.validateVABatch}
          gaEvents={gaEvents}
          maxFileSize={10485760}
        />
      ),
    });
  };

  render() {
    const { docUrl, count, skip, paginate, onSubmit, sampleUrl } = this.props;
    const handleDownloadClick = this.download;
    const items = this.props.items;

    return (
      <div class="content-wrapper">
        {/* passing the new props to the HeaderAction component to support the m-web view */}
        <HeaderAction responsive>
          <div class="btn-toolbar pull-right">
            {sampleUrl && (
              <a class="btn btn-link" href={sampleUrl}>
                Download Sample File
              </a>
            )}

            {docUrl && (
              <a class="btn btn-link" href={docUrl} target="_blank" rel="noopener noreferrer">
                Documentation &nbsp; <i class="i i-external-link" />
              </a>
            )}

            <span className="cta-container">
              <button class="btn btn-primary pull-right" onClick={this.openBatchUploadModal}>
                Upload New Batch
              </button>
            </span>
          </div>
        </HeaderAction>

        <BatchListFilter form="batchListFilter" count={count} onSubmit={onSubmit} />
        <DataTable
          title="Batch Upload"
          columns={[
            batchIdLink,
            batchName,
            totalCount,
            createdAt,
            status,
            batchActions({
              onDownloadClick: handleDownloadClick,
            }),
          ]}
          count={count}
          skip={skip}
          paginate={paginate}
          {...this.props}
          items={items}
        />
      </div>
    );
  }
}

export default connect(
  (state) => ({ session: state.session }),
  (dispatch) =>
    bindActionCreators(
      {
        batchDownload,
        showNotification,
        openModal,
      },
      dispatch,
    ),
)(BatchList);
