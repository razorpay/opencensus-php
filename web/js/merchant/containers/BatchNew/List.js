import { Fragment } from 'react';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';
import { compose, bindActionCreators } from 'redux';

import PopoverComponent, { PopoverBody, PopoverTitle } from 'common/ui/Popover';
import DataTable from 'common/ui/Table/DataTable';
import { batchIdLink, totalCount, batchName, status } from 'common/ui/item/pair';
import { EmptyComponent as emptyComponent } from 'merchant/components/BatchNew/ListAddons';
import BatchListFilter from 'merchant/components/BatchNew/ListFilter';
import { DocLink } from 'merchant/components/DocsLink';
import ShowWhen from 'merchant/components/ShowWhen';
import ListContainer from 'merchant/containers/ListContainer';
import { luminateRow } from 'merchant/reducers/app';
import { batchDownload } from 'merchant/reducers/batches';
import HeaderActions from 'merchant/views/Transactions/v1/BatchRefunds/HeaderActions';
import { openModal as fnOpenModal } from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';

import track from './track';

const batchStatus = {
  ...status,
  value: (item) => (
    <Fragment>
      {status.value(item)}
      {item.status === 'created' && <i class="i i-refresh refresh-batch-btn" />}
    </Fragment>
  ),
};

class BatchList extends ListContainer {
  static defaultProps = {
    extraColumns: [],
  };

  handleDownloadClick = (id) => {
    this.props.gaEvents.trackDownloadProcessedBatchReport();

    this.props.tracking.trackEvent(
      window.rzpQ.chargeAtWill().interaction(`download.list.initiate`),
    );

    const fnBatchDownload = this.props.extraPropBatchDownload || this.props.batchDownload;

    fnBatchDownload(id)
      .then((response) => {
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
    }),
  )
  openUploadModal = (renderUploadModal) => () => {
    const { openModal } = this.props;
    openModal({
      size: 'large',
      component: renderUploadModal(),
    });
    track.batchUpload(this.props.batchType);
  };

  componentDidMount() {
    this.props.gaEvents.trackGoToLinks('Batch Uploads');
  }

  downloadSampleFile = () => {
    this.props.gaEvents.trackSampleFileDownload('From List View');
    track.downloadSample(this.props.batchType);
  };

  trackViewDocumentation = () => {
    track.viewDocumentation(this.props.batchType);
  };

  render() {
    const {
      docUrl,
      uploadUrl,
      sampleUrl,
      extraColumns,
      session,
      onBatchIdChange,
      onBatchSearchCountChange = () => {},
      onClearAnalytics = () => {},
      gaEvents,
      onSearchAnalytics = () => {},
      trackPagination,
      showUploadForAdminOrOwner = false,
    } = this.props;
    const { user } = session;
    const showBatchUploadButton = showUploadForAdminOrOwner ? user?.isAdminOrOwner : true;

    return (
      <div class="content-wrapper batch-upload-wrapper">
        {/* Mobile Header for New Batch Upload Mobile View */}
        <HeaderActions>
          <div className="btn-toolbar pull-right">
            {sampleUrl && (
              <a className="btn btn-link" href={sampleUrl} onClick={this.downloadSampleFile}>
                Download Sample File
              </a>
            )}

            <ShowWhen
              additionalCondition={(usr) => usr.isOrgAllowedFunctionality('external_links')}
            >
              {docUrl && (
                <DocLink
                  class="btn btn-link"
                  href={docUrl}
                  target="_blank"
                  onClick={this.trackViewDocumentation}
                >
                  Documentation &nbsp; <i class="i i-external-link" />
                </DocLink>
              )}
            </ShowWhen>

            {showBatchUploadButton &&
              (this.props.multiBatch ? (
                <div class="pull-right MultiBatch--action">
                  <span className="cta-container">
                    <div class="btn btn-primary">Upload New Batch</div>
                  </span>
                  <PopoverComponent align="bottom" class="MultiBatch--popover">
                    <PopoverTitle>
                      <h4>
                        <strong>Upload New Batch</strong>
                      </h4>
                    </PopoverTitle>
                    <PopoverBody>{this.props.renderBatchOptions(this.openUploadModal)}</PopoverBody>
                  </PopoverComponent>
                </div>
              ) : (
                ((session.mode !== 'live' || !user.isRejected) && (
                  <span className="cta-container">
                    <button
                      class="btn btn-primary pull-right"
                      onClick={this.openUploadModal(this.props.renderUploadModal)}
                    >
                      Click here to upload
                    </button>
                  </span>
                )) ||
                null
              ))}
          </div>
        </HeaderActions>

        <BatchListFilter
          form="batchListFilter"
          onSearchAnalytics={() => {
            gaEvents?.trackSearchFilters();
            onSearchAnalytics?.();
          }}
          ExtraFilterFields={this.props.ExtraFilterFields}
          onBatchIdChange={onBatchIdChange}
          onBatchSearchCountChange={onBatchSearchCountChange}
          onClearAnalytics={onClearAnalytics}
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
          paginate={(params, type) => {
            this.paginate(params);
            trackPagination?.(params.skip, type);
          }}
          onSubmit={this.search}
          EmptyComponent={emptyComponent(
            uploadUrl,
            !this.props.multiBatch ? this.openUploadModal(this.props.renderUploadModal) : undefined,
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
    value: (item) =>
      item.status === 'processed' && (
        <div class="btn-toolbar">
          <button class="btn btn-xs btn-default" onClick={() => onDownloadClick(item.id)}>
            <i class="i i-download" /> Download
          </button>
          {otherBatchActions.map((batchAction) => batchAction(item))}
        </div>
      ),
  };
}

export default compose(
  connect(
    (state) => ({
      session: state.session,
      ...state.batches,
    }),
    (dispatch) =>
      bindActionCreators(
        { batchDownload, openModal: fnOpenModal, luminateRow, ...NotificationsActions },
        dispatch,
      ),
  ),
  // eslint-disable-next-line babel/new-cap
  RTracking(() => window.rzpQ.component('BatchList')),
)(BatchList);
