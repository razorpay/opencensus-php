import { Component } from 'react';
import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';

import DataTable from 'rzp/ui/Table/DataTable';
import { Link } from 'react-router-dom';
import HeaderAction from 'rzp/ui/HeaderAction';
import BatchListFilter from 'merchant/components/BatchNew/ListFilter';
import {
  EmptyComponent,
  batchStatus,
  batchIdLink,
} from 'merchant/components/BatchNew/ListAddons';
import { batchId, totalCount, batchName } from 'rzp/ui/item/pair';
import { openModal } from 'rzp/modules/modals';

import { luminateRow } from 'merchant/modules/app';
import * as NotificationsActions from 'rzp/modules/notifications';
import BatchUpload from './Upload';

import { batchDownload, fetchBatch } from 'merchant/modules/batches';
import {
  trackGoToLinks,
  trackSampleFileDownload,
  trackSearchFilters,
  trackDownloadProcessedBatchReport,
} from './ga';

function batchActions({ mode, sendAll, onDownloadClick }) {
  return {
    title: 'Actions',
    value: item => (
      <div class="btn-toolbar">
        <button
          class="btn btn-xs btn-default"
          onClick={() => onDownloadClick(item.id)}
        >
          <i class="i i-download" /> Download
        </button>
        {/* hide for below statuses  */}
        {['created', 'failure'].indexOf(item.status) < 0 &&
        item.success_count > 0 ? (
          <button
            class="btn btn-default btn-xs"
            onClick={_ => sendAll(item)}
            disabled={!allowSendAllLinks(item)}
          >
            {allowSendAllLinks(item) ? 'Send all links' : 'All links sent'}
          </button>
        ) : null}
      </div>
    ),
  };
}

function allowSendAllLinks(batch) {
  // config object will not be available for older batches
  // duplicate batches will have no success count
  if (Object.keys(batch.config).length) {
    if (
      parseInt(batch.config.sms_notify) > 0 ||
      parseInt(batch.config.email_notify) > 0
    ) {
      //if more than 0 payment link(s) has been sent, disable the btn
      return false;
    } else {
      return true;
    }
  } else {
    //disable for older batches
    false;
  }
}

@connect(null, {
  batchDownload,
  fetchBatch,
  openModal,
  luminateRow,
  ...NotificationsActions,
})
export default class BatchList extends Component {
  dowload = id => {
    let windowRef = window.open('', '_blank');
    trackDownloadProcessedBatchReport();
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
      component: (
        <BatchUpload
          onSave={batch => {
            this.props.luminateRow(batch.id);
          }}
          {...this.props}
        />
      ),
    });
  };

  componentDidMount() {
    trackGoToLinks('Batch Uploads');
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
    let handleDownloadClick = this.dowload;

    return (
      <div class="content-wrapper batch-upload-wrapper">
        <HeaderAction>
          <div class="btn-toolbar pull-right">
            <a
              class="btn btn-link hidden-xs"
              href={sampleUrl}
              onClick={trackSampleFileDownload}
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
          onSearchAnalytics={trackSearchFilters}
        />
        <DataTable
          title="Batch Uploads"
          columns={[
            batchIdLink,
            batchName,
            totalCount,
            batchStatus,
            batchActions({
              mode,
              sendAll,
              onDownloadClick: handleDownloadClick,
            }),
          ]}
          count={count}
          skip={skip}
          paginate={paginate}
          EmptyComponent={EmptyComponent.bind(
            this,
            uploadUrl,
            this.openUploadModal
          )}
          {...this.props}
        />
      </div>
    );
  }
}
