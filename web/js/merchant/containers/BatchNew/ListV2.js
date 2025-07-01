import { Fragment } from 'react';
import { Link } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';
import { compose, bindActionCreators } from 'redux';

import PopoverComponent, { PopoverBody, PopoverTitle } from 'common/ui/Popover';
import ProductWrapper from 'common/ui/ProductWrapper';
import DataTable from 'common/ui/Table/DataTable';
import { batchIdLink, totalCount, batchName, status } from 'common/ui/item/pair';
import { EmptyComponent as emptyComponent } from 'merchant/components/BatchNew/ListAddons';
import BatchListFilter from 'merchant/components/BatchNew/ListFilter';
import { DocLink } from 'merchant/components/DocsLink';
import ShowWhen from 'merchant/components/ShowWhen';
import ListContainer from 'merchant/containers/ListContainer';
import { withRouter } from 'common/deprecated/withRouter';
import { luminateRow } from 'merchant/reducers/app';
import { batchDownload } from 'merchant/reducers/batches';
import { BATCH_TYPE } from 'merchant/views/PaymentPages/PaymentPages/constants';
import { openModal as fnOpenModal } from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';

import track from './track';

const batchStatus = {
  ...status,
  value: (item) => (
    <Fragment>
      {status.value(item)}
      {item.status === 'created' && <i className="i i-refresh refresh-batch-btn" />}
    </Fragment>
  ),
};

class BatchList extends ListContainer {
  constructor(props) {
    super(props);
    const { batchType, user } = props;
    const isBatchPaymentPage = batchType === BATCH_TYPE;
    const isVisible =
      user?.isAllowedView('payment_links_batch_uploads') &&
      user.isPLBatchUploadEnabled &&
      !isBatchPaymentPage &&
      (!user.isSellerAppRole || user.isPaymentLinkBatchEnabledForSellerAppRole);
    this.state = {
      tabsData: [
        { title: 'Payment Links', url: '/paymentlinks', hidden: isBatchPaymentPage },
        {
          title: 'Batch Uploads',
          url: '/paymentlinks/batchuploads',
          hidden: !isVisible,
        },
      ],
    };
  }
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

  openUploadModal = (renderUploadModal) => () => {
    window.rzpQ.onbr().success('dash.pl_action', {
      action: 'Upload_Batch_PL_File',
    });
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

  downloadSampleFile = (e) => {
    const { onSampleFileDownload = () => {}, gaEvents } = this.props;

    gaEvents.trackSampleFileDownload('From List View');
    track.downloadSample(this.props.batchType);

    onSampleFileDownload(e);
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
      propsTabData = [],
      isSampleFileLoading,
      shouldShowSampleDownloadBtn = false,
    } = this.props;
    const { user } = session;
    const showBatchUploadButton = showUploadForAdminOrOwner ? user?.isAdminOrOwner : true;
    const { tabsData } = this.state;
    const tabData = propsTabData?.length > 0 ? propsTabData : tabsData;
    const isRouteDSEnabled = user.isRouteDSEnabled;

    return (
      <ProductWrapper
        tabsData={tabData}
        extra={
          <>
            <ShowWhen additionalCondition={() => sampleUrl}>
              <a className="btn btn-link" href={sampleUrl} onClick={this.downloadSampleFile}>
                Download Sample File
              </a>
            </ShowWhen>
            <ShowWhen additionalCondition={() => !sampleUrl && shouldShowSampleDownloadBtn}>
              <Link
                variant="button"
                onClick={this.downloadSampleFile}
                isDisabled={isSampleFileLoading}
                accessibilityLabel="download-sample-file"
              >
                Download Sample File
              </Link>
            </ShowWhen>
            <ShowWhen
              additionalCondition={(usr) => usr.isOrgAllowedFunctionality('external_links')}
            >
              {docUrl && (
                <DocLink
                  className="btn btn-link"
                  href={docUrl}
                  target="_blank"
                  onClick={this.trackViewDocumentation}
                >
                  Documentation &nbsp; <i className="i i-external-link" />
                </DocLink>
              )}
            </ShowWhen>

            {showBatchUploadButton &&
              (this.props.multiBatch ? (
                <div className="pull-right MultiBatch--action">
                  <button className="btn btn-primary" disabled={isRouteDSEnabled}>
                    Upload New Batch
                  </button>
                  {!isRouteDSEnabled && (
                    <PopoverComponent align="bottom" className="MultiBatch--popover">
                      <PopoverTitle>
                        <h4>
                          <strong>Upload New Batch</strong>
                        </h4>
                      </PopoverTitle>
                      <PopoverBody>
                        {this.props.renderBatchOptions(this.openUploadModal)}
                      </PopoverBody>
                    </PopoverComponent>
                  )}
                </div>
              ) : (
                ((session.mode !== 'live' || !user.isRejected) && (
                  <span className="tabbed-header-actions">
                    <span className="cta-container">
                      <button
                        className="btn btn-primary"
                        onClick={this.openUploadModal(this.props.renderUploadModal)}
                      >
                        Click here to upload
                      </button>
                    </span>
                  </span>
                )) ||
                null
              ))}
          </>
        }
      >
        <content>
          <div className="content-wrapper batch-upload-wrapper">
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
                !this.props.multiBatch
                  ? this.openUploadModal(this.props.renderUploadModal)
                  : undefined,
                this.props.emptyResultsDescription,
              )}
              {...this.props}
            />
          </div>
        </content>
      </ProductWrapper>
    );
  }
}

function batchActions(onDownloadClick, otherBatchActions = []) {
  return {
    title: 'Actions',
    value: (item) =>
      item.status === 'processed' && (
        <div className="btn-toolbar">
          <button className="btn btn-xs btn-default" onClick={() => onDownloadClick(item.id)}>
            <i className="i i-download" /> Download
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
)(withRouter(BatchList));
