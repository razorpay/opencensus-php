import { Component } from 'react';
import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';

import DataTable from 'rzp/ui/Table/DataTable';
import { Link } from 'react-router-dom';
import HeaderAction from 'rzp/ui/HeaderAction';
import BatchListFilter from 'merchant/components/BatchNew/ListFilter';
import { batchId, totalCount, status, batchName } from 'rzp/ui/item/pair';
import { openModal } from 'rzp/modules/modals';
import { luminateRow } from 'merchant/modules/app';

import BatchUpload from './Upload';

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
  openModal,
  luminateRow,
})
export default class BatchList extends Component {
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
            status,
            batchActions({
              mode,
              viewAll,
              issueAll,
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
  value: batch => (
    <NavLink to={`/${batch.type.split('_').join('')}s/batch/${batch.id}`}>
      <code>{batch.id}</code>
    </NavLink>
  ),
};

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
