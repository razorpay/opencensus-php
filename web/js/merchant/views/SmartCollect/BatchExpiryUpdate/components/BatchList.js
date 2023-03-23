import { Component } from 'react';
import { connect } from 'react-redux';
import DataTable from 'common/ui/Table/DataTable';
import ProductWrapper from 'common/ui/ProductWrapper';
import TestModeBanner from 'merchant/components/TestModeBanner';
import { batchIdLink, batchName, totalCount, status, createdAt } from 'common/ui/item/pair';
import { batchDownload } from 'merchant/reducers/batches';
import { showNotification } from 'merchant_common/reducers/notifications';
import BatchListFilter from './BatchListFilter';
import { openModal } from 'merchant_common/reducers/modals';
import BatchUpload from 'merchant/containers/BatchNew/Upload';
import { bindActionCreators } from 'redux';
import setGaTrack from 'merchant/containers/BatchNew/ga';
import { DocLink } from 'merchant/components/DocsLink';
import { checkIfVirtualAccountRoute } from 'merchant/views/SmartCollect/utils';

const gaEvents = setGaTrack('Dashboard - Batch Expiry Update - BU');

function batchActions({ onDownloadClick }) {
  return {
    title: 'Actions',
    value: (item) => (
      <div className="btn-toolbar">
        <button className="btn btn-xs btn-default" onClick={() => onDownloadClick(item.id)}>
          Download Report
        </button>
      </div>
    ),
  };
}
class BatchList extends Component {
  constructor(props) {
    super(props);
    this.state = {
      tabsData: [
        {
          title: 'Customer Identifiers',
          url: '/smartcollect/virtualaccounts',
          isActive: checkIfVirtualAccountRoute,
        },
        { title: 'Payments', url: '/smartcollect/payments' },
        {
          title: 'Batch Expiry Update',
          url: '/smartcollect/batchuploads',
          hidden: !props.isVaEditBulkMid,
        },
      ],
    };
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    if (nextProps.isVaEditBulkMid != this.props.isVaEditBulkMid) {
      this.setState({
        tabsData: [
          {
            title: 'Customer Identifiers',
            url: '/smartcollect/virtualaccounts',
            isActive: checkIfVirtualAccountRoute,
          },
          { title: 'Payments', url: '/smartcollect/payments' },
          {
            title: 'Batch Expiry Update',
            url: '/smartcollect/batchuploads',
            hidden: !nextProps.isVaEditBulkMid,
          },
        ],
      });
    }
  }

  download = (id) => {
    const windowRef = window.open('', '_blank');
    this.props
      .batchDownload(id)
      .then((response) => {
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

  openBatchUploadModal = () => {
    const { docUrl, sampleUrl, batchType, createVABatch, validateVABatch } = this.props;
    this.props.openModal({
      size: 'large',
      component: (
        <BatchUpload
          docUrl={docUrl}
          sampleUrl={sampleUrl}
          closeUrl="/smartcollect/batchuploads"
          ctaText="Create Batch"
          pendingText="Creating & Sending..."
          batchType={batchType}
          maxRows={10000}
          createBatch={createVABatch}
          validateBatch={validateVABatch}
          gaEvents={gaEvents}
          maxFileSize={10485760}
        />
      ),
    });
  };

  render() {
    const { docUrl, count, skip, paginate, onSubmit, sampleUrl, items } = this.props;
    const handleDownloadClick = this.download;
    const { tabsData } = this.state;

    return (
      <ProductWrapper
        tabsData={tabsData}
        extra={
          <>
            {sampleUrl && (
              <a className="btn btn-link" href={sampleUrl}>
                Download Sample File
              </a>
            )}

            {docUrl && (
              <DocLink className="btn btn-link" href={docUrl}>
                Documentation <i className="i i-external-link" />
              </DocLink>
            )}
            <span className="tabbed-header-actions">
              <span className="cta-container">
                <button className="btn btn-primary" onClick={this.openBatchUploadModal}>
                  Upload New Batch
                </button>
              </span>
            </span>
          </>
        }
      >
        <content>
          <div className="content-wrapper">
            <TestModeBanner />

            <BatchListFilter form="batchListFilter" count={count} onSubmit={onSubmit} />
            <DataTable
              title="Batch Upload"
              columns={[
                batchIdLink,
                batchName,
                totalCount,
                createdAt,
                status,
                batchActions({
                  onDownloadClick: handleDownloadClick,
                }),
              ]}
              count={count}
              skip={skip}
              paginate={paginate}
              {...this.props}
              items={items}
            />
          </div>
        </content>
      </ProductWrapper>
    );
  }
}

export default connect(
  (state) => ({ session: state.session, isVaEditBulkMid: state.virtualaccount.isVaEditBulkMid }),
  (dispatch) =>
    bindActionCreators(
      {
        batchDownload,
        showNotification,
        openModal,
      },
      dispatch,
    ),
)(BatchList);
