import { Fragment } from 'react';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';
// eslint-disable-next-line no-restricted-imports
import HeaderAction from 'common/ui/HeaderAction';
import { compose, bindActionCreators } from 'redux';
import DataTable from 'common/ui/Table/DataTable';
import ListContainer from 'merchant/containers/ListContainer';
import BatchListFilter from 'merchant/components/BatchNew/ListFilter';
import { EmptyComponent as emptyComponent } from 'merchant/components/BatchNew/ListAddons';
import { batchIdLink, totalCount, batchName, status } from 'common/ui/item/pair';
import { openModal as fnOpenModal } from 'merchant_common/reducers/modals';
import { luminateRow } from 'merchant/reducers/app';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import { batchDownload } from 'merchant/reducers/batches';
import PopoverComponent, { PopoverBody, PopoverTitle } from 'common/ui/Popover';
import ShowWhen from 'merchant/components/ShowWhen';
import { DocLink } from 'merchant/components/DocsLink';
import track from './track';
import { TransactionsEntityRoute } from 'merchant/views/Transactions/v2/common/constants';

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
    const isTxnsBatchPayments = location.pathname.includes(TransactionsEntityRoute.BATCH_PAYMENTS);

    return (
      <div class="content-wrapper batch-upload-wrapper">
        {/* If it is isTxnsBatchPayments then don't keep the batch upload CTA sticky */}
        <div
          class={`btn-toolbar pull-right ${
            isTxnsBatchPayments ? 'filter-no-margin' : 'header-btns'
          } hidden-xs`}
        >
          {sampleUrl && (
            <a class="btn btn-link hidden-xs" href={sampleUrl} onClick={this.downloadSampleFile}>
              Download Sample File
            </a>
          )}
          <ShowWhen additionalCondition={(usr) => usr.isOrgAllowedFunctionality('external_links')}>
            {docUrl && (
              <DocLink
                class="btn btn-link hidden-xs"
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
                <div class="btn btn-primary">Upload New Batch</div>
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
                <button
                  class="btn btn-primary pull-right"
                  onClick={this.openUploadModal(this.props.renderUploadModal)}
                >
                  Click here to upload
                </button>
              )) ||
              null
            ))}
        </div>
        {/* Mobile Header for New Batch Upload Mobile View */}
        <HeaderAction responsive>
          <div className="btn-toolbar pull-right hidden-lg">
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
        </HeaderAction>

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
