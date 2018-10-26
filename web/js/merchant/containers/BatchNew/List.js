import { Fragment } from 'react';
import { connect } from 'react-redux';

import DataTable from 'rzp/ui/Table/DataTable';
import ListContainer from 'merchant/containers/ListContainer';
import BatchListFilter from 'merchant/components/BatchNew/ListFilter';
import { EmptyComponent } from 'merchant/components/BatchNew/ListAddons';
import { batchIdLink, totalCount, batchName, status } from 'rzp/ui/item/pair';
import { openModal } from 'rzp/modules/modals';

import { luminateRow } from 'merchant/modules/app';
import * as NotificationsActions from 'rzp/modules/notifications';

import { batchDownload } from 'merchant/modules/batches';
import Popover, { PopoverBody, PopoverTitle } from 'rzp/ui/Popover';

const batchStatus = {
  ...status,
  value: item => (
    <Fragment>
      {status.value(item)}
      {item.status === 'created' && <i class="i i-refresh refresh-batch-btn" />}
    </Fragment>
  ),
};

@connect(
  state => ({
    ...state.batches,
  }),
  {
    batchDownload,
    openModal,
    luminateRow,
    ...NotificationsActions,
  }
)
export default class BatchList extends ListContainer {
  static defaultProps = {
    extraColumns: [],
  };

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

  openUploadModal = renderUploadModal => () => {
    this.props.openModal({
      size: 'large',
      component: renderUploadModal(),
    });
  };

  componentDidMount() {
    this.props.gaEvents.trackGoToLinks('Batch Uploads');
  }

  render() {
    let { docUrl, uploadUrl, sampleUrl, extraColumns } = this.props;

    return (
      <div class="content-wrapper batch-upload-wrapper">
        <div class="btn-toolbar pull-right header-btns">
          {sampleUrl && (
            <a
              class="btn btn-link hidden-xs"
              href={sampleUrl}
              onClick={this.props.gaEvents.trackSampleFileDownload(
                'From List View'
              )}
            >
              Download Sample File
            </a>
          )}
          {docUrl && (
            <a class="btn btn-link hidden-xs" href={docUrl} target="_blank">
              Documentation &nbsp;
              <i class="i i-external-link" />
            </a>
          )}

          {this.props.multiBatch ? (
            <div class="pull-right MultiBatch--action">
              <div class="btn btn-primary">Upload New Batch</div>
              <Popover align="bottom" class="MultiBatch--popover">
                <PopoverTitle>
                  <h3>Upload New Batch</h3>
                </PopoverTitle>
                <PopoverBody>
                  {this.props.renderBatchOptions(this.openUploadModal)}
                </PopoverBody>
              </Popover>
            </div>
          ) : (
            <button
              class="btn btn-primary pull-right"
              onClick={this.openUploadModal(this.props.renderUploadModal)}
            >
              Click here to upload
            </button>
          )}
        </div>

        <BatchListFilter
          form="batchListFilter"
          onSearchAnalytics={this.props.gaEvents.trackSearchFilters}
          ExtraFilterFields={this.props.ExtraFilterFields}
        />
        <DataTable
          title="Batch Uploads"
          columns={[
            batchIdLink,
            batchName,
            totalCount,
            ...extraColumns,
            batchStatus,
            batchActions(this.handleDownloadClick, this.props.batchActions),
          ]}
          count={this.state.count}
          skip={this.state.skip}
          paginate={this.paginate}
          onSubmit={this.search}
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
