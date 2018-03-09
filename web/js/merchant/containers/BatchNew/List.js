import { Component } from 'react';
import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';

import DataTable from 'rzp/ui/Table/DataTable';
import { Link } from 'react-router-dom';
import HeaderAction from 'rzp/ui/HeaderAction';
import BatchListFilter from 'merchant/components/BatchNew/ListFilter';
import {
  EmptyComponent,
  BatchStatus,
  BatchNavLink,
} from 'merchant/components/BatchNew/ListAddons';
import { batchId, totalCount, batchName } from 'rzp/ui/item/pair';
import { openModal } from 'rzp/modules/modals';

import { luminateRow } from 'merchant/modules/app';
import * as NotificationsActions from 'rzp/modules/notifications';
import BatchUpload from './Upload';

import { batchDownload, fetchBatch } from 'merchant/modules/batches';

function batchActions({
  mode,
  viewAll,
  issueAll,
  onDownloadClick,
  issuableIdList,
}) {
  return {
    viewAll,
    issueAll,
    title: 'Actions',
    value: item => (
      <div class="btn-toolbar">
        <button
          class="btn btn-xs btn-default"
          onClick={() => onDownloadClick(item.id)}
        >
          <i class="i i-download" /> Download
        </button>
        {do {
          if (item.type === 'payment_link') {
            if (viewAll) {
              <button
                class="btn btn-default btn-xs"
                onClick={_ => viewAll(item)}
              >
                view all links
              </button>;
            }

            {
              /*issuableIdList is present only in case of Payment Links*/
            }
            if (
              issueAll &&
              item.status === 'processed' &&
              (!issuableIdList || issuableIdList.indexOf(item.id) > -1)
            ) {
              <button
                class="btn btn-default btn-xs"
                onClick={_ => issueAll(item)}
              >
                Send all links
              </button>;
            } else {
              <button class="btn btn-default btn-xs" disabled={true}>
                All Links Sent
              </button>;
            }
          }
        }}
      </div>
    ),
  };
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

  refetchBatchDetails = batchId => {
    this.props.fetchBatch(batchId);
  };

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
      viewAll,
      issueAll,
      issuableIdList,
    } = this.props;
    let handleDownloadClick = this.dowload;

    return (
      <div class="content-wrapper batch-upload-wrapper">
        <HeaderAction>
          <div class="btn-toolbar pull-right">
            <a class="btn btn-link" href={sampleUrl}>
              Download Sample File
            </a>
            {docUrl && (
              <a class="btn btn-link" href={docUrl} target="_blank">
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
        />
        <DataTable
          title="Batch Uploads"
          columns={[
            batchIdLink,
            batchName,
            totalCount,
            batchStatus(this.refetchBatchDetails),
            batchActions({
              mode,
              viewAll,
              issueAll,
              onDownloadClick: handleDownloadClick,
              issuableIdList,
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

const batchIdLink = {
  title: 'Batch ID',
  value: batch => <BatchNavLink batchId={batch.id} batchType={batch.type} />,
};

/**
 * Render customized `Status Pill` label for batches.
 * Add refresh btn if the batch has just been created.
 */
const batchStatus = refetchBatchDetails => {
  return {
    title: 'Status',
    value: item => (
      <BatchStatus
        id={item.id}
        status={item.status}
        onRefetchBatchDetails={refetchBatchDetails}
      />
    ),
  };
};
