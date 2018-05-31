import { Component, Fragment } from 'react';
import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';

import DataTable from 'rzp/ui/Table/DataTable';
import { Link } from 'react-router-dom';
import HeaderAction from 'rzp/ui/HeaderAction';
import BatchListFilter from 'merchant/components/BatchNew/ListFilter';
import { EmptyComponent } from 'merchant/components/BatchNew/ListAddons';
import { batchIdLink, totalCount, batchName, status } from 'rzp/ui/item/pair';
import { openModal } from 'rzp/modules/modals';

import { luminateRow } from 'merchant/modules/app';
import * as NotificationsActions from 'rzp/modules/notifications';

import { batchDownload, fetchBatch } from 'merchant/modules/batches';

const batchStatus = {
  ...status,
  value: item => (
    <Fragment>
      {status.value(item)}
      {item.status === 'created' && (
        <i class="i i-refresh spin refetch-batch-btn" />
      )}
    </Fragment>
  ),
};

@connect(null, {
  batchDownload,
  fetchBatch,
  openModal,
  luminateRow,
  ...NotificationsActions,
})
export default class BatchList extends Component {
  handleDownloadClick = id => {
    let windowRef = window.open('', '_blank');
    this.props.gaEvents.trackDownloadProcessedBatchReport();
    this.props
      .batchDownload(id)
      .then(response => {
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

  openUploadModal = () => {
    this.props.openModal({
      size: 'large',
      component: this.props.renderUploadModal(),
    });
  };

  componentDidMount() {
    this.props.gaEvents.trackGoToLinks('Batch Uploads');
  }

  render() {
    let {
      mode,
      docUrl,
      count,
      skip,
      paginate,
      onSubmit,
      uploadUrl,
      sampleUrl,
      sendAll,
    } = this.props;

    return (
      <div class="content-wrapper batch-upload-wrapper">
        <HeaderAction>
          <div class="btn-toolbar pull-right">
            <a
              class="btn btn-link hidden-xs"
              href={sampleUrl}
              onClick={this.props.gaEvents.trackSampleFileDownload(
                'From List View'
              )}
            >
              Download Sample File
            </a>
            {docUrl && (
              <a class="btn btn-link hidden-xs" href={docUrl} target="_blank">
                Documentation &nbsp;
                <i class="i i-external-link" />
              </a>
            )}

            <button
              class="btn btn-primary pull-right"
              onClick={this.openUploadModal}
            >
              Click here to upload
            </button>
          </div>
        </HeaderAction>

        <BatchListFilter
          form="batchListFilter"
          count={count}
          onSubmit={onSubmit}
          onSearchAnalytics={this.props.gaEvents.trackSearchFilters}
        />
        <DataTable
          title="Batch Uploads"
          columns={[
            batchIdLink,
            batchName,
            totalCount,
            batchStatus,
            batchActions(this.handleDownloadClick, this.props.batchActions),
          ]}
          count={count}
          skip={skip}
          paginate={paginate}
          EmptyComponent={EmptyComponent(uploadUrl, this.openUploadModal)}
          {...this.props}
        />
      </div>
    );
  }
}

function batchActions(onDownloadClick, otherBatchActions = []) {
  return {
    title: 'Actions',
    value: item =>
      item.status === 'processed' && (
        <div class="btn-toolbar">
          <button
            class="btn btn-xs btn-default"
            onClick={() => onDownloadClick(item.id)}
          >
            <i class="i i-download" /> Download
          </button>
          {otherBatchActions.map(batchAction => batchAction(item))}
        </div>
      ),
  };
}
