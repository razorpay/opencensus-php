import { Fragment } from 'react';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';

import DataTable from 'common/ui/Table/DataTable';
import ListContainer from 'merchant/containers/ListContainer';
import BatchListFilter from 'merchant/components/BatchNew/ListFilter';
import { EmptyComponent } from 'merchant/components/BatchNew/ListAddons';
import {
  batchIdLink,
  totalCount,
  batchName,
  status,
} from 'common/ui/item/pair';
import { openModal } from 'merchant_common/reducers/modals';

import { luminateRow } from 'merchant/reducers/app';
import * as NotificationsActions from 'merchant_common/reducers/notifications';

import { batchDownload } from 'merchant/reducers/batches';
import Popover, { PopoverBody, PopoverTitle } from 'common/ui/Popover';
import ShowWhen from 'merchant/components/ShowWhen';
import { DocLink } from 'merchant/components/DocsLink'

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
    session: state.session,
    ...state.batches,
  }),
  {
    batchDownload,
    openModal,
    luminateRow,
    ...NotificationsActions,
  }
)
@RTracking(() => window.rzpQ.component('BatchList'))
export default class BatchList extends ListContainer {
  static defaultProps = {
    extraColumns: [],
  };

  handleDownloadClick = id => {
    this.props.gaEvents.trackDownloadProcessedBatchReport();

    const batchDownload =
      this.props.extraPropBatchDownload || this.props.batchDownload;

    batchDownload(id)
      .then(response => {
        window.location = response.data.url;
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors,
        });
      });
  };

  @RTracking(() =>
    window.rzpQ.onbr().success('dash.pl_action', {
      action: 'Upload_Batch_PL_File',
    })
  )
  openUploadModal = renderUploadModal => () => {
    const { openModal } = this.props;
    openModal({
      size: 'large',
      component: renderUploadModal(),
    });
  };

  componentDidMount() {
    this.props.gaEvents.trackGoToLinks('Batch Uploads');
  }

  render() {
    let { docUrl, uploadUrl, sampleUrl, extraColumns, session } = this.props,
      { user } = session;

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
          <ShowWhen
            additionalCondition={user =>
              user.isOrgAllowedFunctionality('external_links')
            }
          >
            {docUrl && (
              <DocLink class="btn btn-link hidden-xs" href={docUrl} target="_blank">
                Documentation &nbsp;
                <i class="i i-external-link" />
              </DocLink>
            )}
          </ShowWhen>

          {this.props.multiBatch ? (
            <div class="pull-right MultiBatch--action">
              <div class="btn btn-primary">Upload New Batch</div>
              <Popover align="bottom" class="MultiBatch--popover">
                <PopoverTitle>
                  <h4>
                    <strong>Upload New Batch</strong>
                  </h4>
                </PopoverTitle>
                <PopoverBody>
                  {this.props.renderBatchOptions(this.openUploadModal)}
                </PopoverBody>
              </Popover>
            </div>
          ) : (
            ((session.mode !== 'live' || !user.isRejected) && (
              <button
                class="btn btn-primary pull-right"
                onClick={this.openUploadModal(this.props.renderUploadModal)}
              >
                Click here to upload
              </button>
            )) ||
            null
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
          EmptyComponent={EmptyComponent(
            uploadUrl,
            !this.props.multiBatch
              ? this.openUploadModal(this.props.renderUploadModal)
              : undefined,
            this.props.emptyResultsDescription,
          )}
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
