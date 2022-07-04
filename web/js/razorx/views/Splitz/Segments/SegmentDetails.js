import React from 'react';
import Spinner from 'common/ui/Spinner';
import PropTypes from 'prop-types';
import { withRouter } from 'react-router-dom';
import adminFetch from 'razorx/helpers/admin-fetch';
import { formatDate } from 'razorx/helpers/utils';
import { splitzFetch } from 'razorx/helpers/fetch';
import { openModal, notifyError, notifySuccess } from 'razorx/components/Modal';
import AsyncButton from 'razorx/components/ui/AsyncButton';
import AddEditSegment from './AddEditSegment';
import { SEGMENT_DELETE, SEGMENT_EVALUATE_IN_BLOOM } from './constants';

// eslint-disable-next-line react/no-unsafe
@withRouter
export default class SegmentDetails extends React.Component {
  evaluatorIds = null;

  state = {
    data: null,
    isFetchingSegment: true,
    status: true,
    fileUrl: null,
    evaluatorIdList: null,
  };

  componentDidMount() {
    this.fetch(this.props.segmentId);
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    if (this.props.segmentId !== nextProps.segmentId) {
      this.fetch(nextProps.segmentId);
    }
  }

  fetch(segmentId) {
    if (!segmentId) {
      return;
    }

    this.setState({
      data: null,
      isFetchingSegment: true,
      status: null,
    });

    // Fetch Segment Properties
    const expands = ['files'];
    splitzFetch({
      url: 'segment.v1.SegmentAPI/Get',
      data: {
        segmentID: segmentId,
        expands,
      },
    })
      .then((res) => {
        this.setState({
          isFetchingSegment: false,
          data: res.items, // TODO: fix from API
          fileUrl: Object.values(res?.fileUrl || [])[0],
        });
      })
      .catch(() => {
        this.setState({
          isFetchingSegment: false,
        });
      });

    // Get Segment Status
    splitzFetch({
      url: 'segment.v1.SegmentAPI/Status',
      data: {
        segmentID: segmentId,
      },
    })
      .then((res) => {
        this.setState({
          status: res.status,
        });
      })
      .catch(() => {
        this.setState({
          status: 'Error occurred while fetching status',
        });
      });
  }

  viewFile = (fileId) => {
    const bodyFormData = new FormData();
    bodyFormData.append('mode', 'live');
    bodyFormData.append('method', 'GET');
    bodyFormData.append('auth', 'admin');
    bodyFormData.append('file', '');

    adminFetch({
      url: `/makeapicall/admin-ufh/file/${fileId}/get-signed-url`,
      method: 'POST',
      data: bodyFormData,
      headers: { 'Content-Type': 'multipart/form-data' },
    })
      .then((res) => {
        if (res.signed_url) {
          window.open(res.signed_url);
        } else {
          console.error('File Download Error: ', res);
        }
      })
      .catch((err) => {
        console.error('File Download Error: ', err);
      });
  };

  storeEvaluatorIds = (e) => {
    const { target = {} } = e;
    const { value = '' } = target || {};
    this.evaluatorIds = value;
  };

  evaluateSegmentId = () => {
    const { segmentId } = this.props;

    if (this.evaluatorIds.trim() !== '') {
      splitzFetch({
        url: SEGMENT_EVALUATE_IN_BLOOM,
        data: {
          segmentID: segmentId,
          evaluatorID: this.evaluatorIds.replace(/ /g, '').split(','),
        },
      }).then((res) => {
        this.setState({
          evaluatorIdList: res.Evaluate,
        }).catch((err) => {
          notifyError(err);
        });
      });
    }
  };

  deleteSegment = () => {
    const { segmentId } = this.props;
    splitzFetch({
      url: SEGMENT_DELETE,
      data: {
        segmentID: segmentId,
      },
    })
      .then(() => {
        this.fetch(segmentId);
        notifySuccess('Success: Segment deleted!');
      })
      .catch((err) => {
        notifyError(err);
      });
  };

  showEditSegment = () => {
    openModal(<AddEditSegment isEdit data={this.state.data} />);
  };

  // onEdit = () => this.fetch(this.props.segmentId);

  render() {
    const { isFetchingSegment, status, data, fileUrl, evaluatorIdList } = this.state;
    const { segmentId } = this.props;

    const isFetching = isFetchingSegment;
    let content;

    const evaluatedIds = evaluatorIdList && (
      <div className="id-results-box">
        {evaluatorIdList?.map(({ evaluatorID, isPresent }) => {
          return (
            <div key={evaluatorID}>
              {evaluatorID} : {isPresent}
            </div>
          );
        })}
      </div>
    );

    if (!segmentId) {
      content = null;
    } else if (isFetching) {
      content = <div className="spinner center" />;
    } else if (!isFetching && !data) {
      content = (
        <div className="page-center empty-entity">
          <i className="i-layers" />
          <div className="description">
            <div>ID: {segmentId}</div>
            No segment found!
          </div>
        </div>
      );
    } else {
      content = (
        <div className="entity-details">
          <div className="sub-description">
            <span>
              <b>ID:</b> {data.id}
            </span>
            <span className="to-right">
              <a className="link text-bold" onClick={this.showEditSegment}>
                Edit Segment
              </a>
            </span>
          </div>
          <div className="pad-highlight">
            <div className="title">{data.name}</div>
            <div className="description">
              {data.description}
              <div className="sub-description">
                <b>Created at</b> {formatDate(data.created_at)}
              </div>
            </div>
            <br />
            <br />
          </div>
          <br />
          <br />
          <div className="flex-row">
            <div className="flex-row-item">
              <div className="label">Status</div>
            </div>
          </div>
          {!status && <Spinner />}
          {status && <div>{status}</div>}
          <br />
          <br />
          <div className="flex-row" style={{ justifyContent: 'space-between' }}>
            <div className="flex-row-item">
              <div className="label">Entries</div>
              <span className="square-pills label-semi-muted">{data.entries}</span>
            </div>
          </div>
          <br />
          <br />
          {data.inputFileID && (
            <div className="flex-row">
              <div className="flex-row-item">
                <div className="label">Input File</div>
                <div className="sub-description column">
                  <div>
                    <b>ID: </b> {data.inputFileID}
                  </div>
                </div>
                <div className="link">
                  <a href={fileUrl}>View File</a>
                </div>
              </div>
            </div>
          )}
          {data.source_type === 'SQL' && (
            <div>
              <br />
              <br />
              <div className="flex-row">
                <div className="flex-row-item">
                  <div className="label">Cron Expression</div>
                  <div>{data.cron_expression}</div>
                </div>
              </div>
              <br />
              <br />
              <div className="flex-row">
                <div className="flex-row-item">
                  <div className="label">SQL Query</div>
                  <div>{data.sql_query}</div>
                </div>
              </div>
            </div>
          )}
          <br />
          <br />
          <div>
            <div className="label">Evaluate IDs</div>
            <input type="text" onChange={this.storeEvaluatorIds} />
            <AsyncButton type="button" className="btn" onClick={this.evaluateSegmentId}>
              Evaluate
            </AsyncButton>
            <br />
            <span className="sub-description">
              *For evaluating multiple ids, use comma seperated strings
            </span>
            {evaluatedIds}
          </div>
          <br />
          <div>
            <AsyncButton
              type="button"
              className="btn btn-delete"
              confirm="Are you sure you want to delete the segment?"
              onClick={this.deleteSegment}
            >
              Delete Segment
            </AsyncButton>
          </div>
        </div>
      );
    }

    return <div className="entity-container">{content}</div>;
  }
}

SegmentDetails.propTypes = {
  segmentId: PropTypes.string.isRequired,
};
