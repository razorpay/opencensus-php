import React from 'react';
import PropTypes from 'prop-types';
import { withRouter } from 'common/deprecated/withRouter';
import { observer } from 'mobx-react';
import { openModal } from 'razorx/components/Modal';
import Collection from 'razorx/model/collection';
import { splitzFetch } from 'razorx/helpers/fetch';
import AddEditSegment from './AddEditSegment';
import SegmentList from './SegmentList';
import SegmentDetails from './SegmentDetails';

@observer
class Segments extends React.Component {
  collection = new Collection({
    isSplitz: true,
    fetchFn: splitzFetch,
    data: {
      url: 'segment.v1.SegmentAPI/List',
    },
  });

  showAddEditSegment = () => openModal(<AddEditSegment collection={this.collection} />);

  render() {
    const segmentId = this.props.match.params.id;

    return (
      <div className="parent-container features-container">
        <div className="header">
          <span className="title">Segments</span>
          <div className="btn-group">
            <button className="btn btn--primary" onClick={this.showAddEditSegment}>
              + New Segment
            </button>
          </div>
        </div>
        <div className="container-group">
          <SegmentList collection={this.collection} />
          <SegmentDetails segmentId={segmentId} collection={this.collection} />
        </div>
      </div>
    );
  }
}

Segments.propTypes = {
  match: PropTypes.object.isRequired,
};

export default withRouter(Segments);
