import { Component } from 'react';
import { connect } from 'react-redux';
import DataTable from 'rzp/ui/Table/DataTable';
import { Link } from 'react-router-dom';
import HeaderAction from 'rzp/ui/HeaderAction';
import BatchListFilter from 'merchant/components/Batch/ListFilter';
import { batchId, totalCount, status, createdAt } from 'rzp/ui/item/pair';
import { batchDownload } from 'merchant/modules/batches';
import * as NotificationsActions from 'rzp/modules/notifications';

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

  render() {
    let {
      mode,
      docUrl,
      count,
      skip,
      paginate,
      onSubmit,
      uploadUrl,
      viewAll,
      issueAll,
      issuableIdList,
    } = this.props;
    let handleDownloadClick = this.dowload;

    return (
      <div class="content-wrapper">
        <HeaderAction>
          <div class="btn-toolbar pull-right">
            {docUrl && (
              <a class="btn btn-link" href={docUrl} target="_blank">
                Documentation &nbsp;
                <i class="i i-external-link" />
              </a>
            )}

            <Link class="btn btn-primary pull-right" to={uploadUrl}>
              Click here to upload
            </Link>
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
            createdAt,
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
          {...this.props}
        />
      </div>
    );
  }
}
