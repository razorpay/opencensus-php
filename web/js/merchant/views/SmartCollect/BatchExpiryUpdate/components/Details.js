import React from 'react';
import { connect } from 'react-redux';
import Time from 'common/ui/Time';
import Spinner from 'common/ui/Spinner';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import { BatchUploadStatusLabel } from 'merchant/components/StatusLabel';
import { fetchBatchAjax, batchDownload } from 'merchant/reducers/batches';
import { showNotification } from 'merchant_common/reducers/notifications';
import { bindActionCreators } from 'redux';
class VABatchDetails extends React.Component {
  state = {
    loading: false,
    batchDetails: {},
  };

  UNSAFE_componentWillMount() {
    this.fetchBatchDetail(this.props.id);
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.fetchBatchDetail(nextProps.id);
    }
  }

  fetchBatchDetail(id) {
    this.setState({ loading: true });
    fetchBatchAjax(id)
      .then((resp) => {
        this.setState({ loading: false });
        if (resp) {
          this.setState({ batchDetails: resp.batch });
        }
      })
      .catch((err) => {
        this.setState({ loading: false });
        this.props.showNotification({
          type: 'error',
          message: err.errors[0],
        });
      });
  }

  onDowloadClick = (id) => {
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

  render() {
    const isLoading = this.state.loading;
    const { batchDetails } = this.state;
    return (
      <div class="content-wrapper content-sm txn-details batch--details">
        {isLoading ? (
          <div class="page-spinner-container">
            <Spinner />
          </div>
        ) : (
          <div class="panel panel-default SliderPanel">
            <div class="panel-heading">
              <i class="i i-plan text-main icon--formal" /> <strong>{batchDetails?.id}</strong>
            </div>

            <div class="SliderPanel__Body">
              <div class="panel-body">
                <div class="download-report-card">
                  <span class="drc-label">
                    Download the report containing Virtual Accounts data for this batch.
                  </span>
                  <span className="cta-container">
                    <button
                      onClick={() => {
                        this.onDowloadClick(batchDetails.id);
                      }}
                      class="btn btn-primary pull-right"
                    >
                      Download Report
                    </button>
                  </span>
                </div>
                <div>
                  <table class="batch-process-details">
                    <tbody>
                      <tr>
                        <td colSpan="2">
                          <div>Total Rows Processed</div>
                          <div class="total-rows-processed-val">{batchDetails?.total_count}</div>
                        </td>
                      </tr>
                      <tr>
                        <td>
                          <div>Expiry Date Updated</div>
                          <div class="expiry-date-updated">{batchDetails?.success_count}</div>
                        </td>
                        <td>
                          {' '}
                          <div>Expiry Date Update Failed</div>
                          <div class="expiry-date-update-failed">{batchDetails?.failure_count}</div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                  <EntityDetailRow label="Status">
                    <BatchUploadStatusLabel status={batchDetails?.status} />
                  </EntityDetailRow>

                  <EntityDetailRow label="Created At">
                    <Time value={batchDetails?.created_at} format="DD MMM YYYY" />
                  </EntityDetailRow>

                  <hr />
                  {batchDetails?.failure_count > 0 && (
                    <p class="process-instant-batch">
                      {' '}
                      <img src="https://cdn.razorpay.com/static/assets/notifs/instant-refunds.svg" />{' '}
                      Some rows in this batch were not processed due to errors. Please &nbsp;{' '}
                      <strong
                        onClick={() => {
                          this.onDowloadClick(batchDetails.id);
                        }}
                        style={{ color: 'rgb(82, 143, 240)', cursor: 'pointer' }}
                      >
                        download the report
                      </strong>{' '}
                      containing accounts data for this batch,
                    </p>
                  )}
                </div>
              </div>
            </div>
          </div>
        )}
      </div>
    );
  }
}

export default connect(
  (state) => ({ session: state.session }),
  (dispatch) =>
    bindActionCreators(
      {
        fetchBatchAjax,
        showNotification,
        batchDownload,
      },
      dispatch,
    ),
)(VABatchDetails);
