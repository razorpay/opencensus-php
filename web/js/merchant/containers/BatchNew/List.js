import { Component } from 'react';
import { connect } from 'react-redux';
import DataTable from 'rzp/ui/Table/DataTable';
import { Link } from 'react-router-dom';
import HeaderAction from 'rzp/ui/HeaderAction';
import BatchListFilter from 'merchant/components/BatchNew/ListFilter';
import { batchId, totalCount, status } from 'rzp/ui/item/pair';
import { batchDownload } from 'merchant/modules/batches';
import { openModal } from 'rzp/modules/modals';
import { luminateRow } from 'merchant/modules/app';

import BatchUpload from './Upload';

function batchActions({
  mode,
  viewAll,
  issueAll,
  onDownloadClick,
  issuableIdList,
  luminateRow,
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
          Download
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
                Issue all links
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
  openModal,
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
      component: <BatchUpload {...this.props} />,
      onSave: batch => {
        this.props.luminateRow(batch.id);
      },
    });
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
            batchId,
            totalCount,
            status,
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

/**
 * Render this component when there are not batch row.
 */
const EmptyComponent = (uploadUrl, openModalFunc) => {
  return (
    <div class="empty-table-message">
      <h3>No Batch Files Found</h3>
      <h5 class="helper">
        A Batch file consists of a group of payment links can be generated in
        bulk. Simply upload a file containing all the information and accept
        payments instantly.
      </h5>
      <button class="btn btn-default" onClick={openModalFunc}>
        Start Uploading
      </button>
    </div>
  );
};
