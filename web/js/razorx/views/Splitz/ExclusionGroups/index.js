import React from 'react';
import PropTypes from 'prop-types';
import { withRouter } from 'common/deprecated/withRouter';
import { observer } from 'mobx-react';
import { openModal } from 'razorx/components/Modal';
import Collection from 'razorx/model/collection';
import { splitzFetch } from 'razorx/helpers/fetch';
import AddEditGroup from './AddEditGroup';
import GroupList from './GroupList';
import GroupDetails from './GroupDetails';

@observer
class ExclusionGroups extends React.Component {
  collection = new Collection({
    isSplitz: true,
    fetchFn: splitzFetch,
    data: {
      url: 'exclusion_group.v1.ExclusionGroupAPI/List',
    },
  });

  showAddEditGroup = () => openModal(<AddEditGroup collection={this.collection} />);

  render() {
    const groupId = this.props.match.params.id;

    return (
      <div className="parent-container features-container">
        <div className="header">
          <span className="title">Exclusion Groups</span>
          <div className="btn-group">
            <button className="btn btn--primary" onClick={this.showAddEditGroup}>
              + New Exclusion Group
            </button>
          </div>
        </div>
        <div className="container-group">
          <GroupList collection={this.collection} />
          <GroupDetails groupId={groupId} />
        </div>
      </div>
    );
  }
}

ExclusionGroups.propTypes = {
  match: PropTypes.object.isRequired,
};

export default withRouter(ExclusionGroups);
