import { withRouter } from 'common/deprecated/withRouter';
import { observer } from 'mobx-react';
import { openModal, notifyError } from 'razorx/components/Modal';
import FeaturesModal from './Modal';
import List from './List';
import Entity from './Entity';
import React from 'react';

@observer
class Features extends React.Component {
  showFeatureModal = (_) => {
    openModal(<FeaturesModal />);
  };

  showJSONModal = (_) => {
    if (!window.CodeFlask) {
      notifyError('JSON Editor is missing. Reload page / check your Network!');
      return;
    }

    openModal(<FeaturesModal JSONView />);
  };

  render() {
    return (
      <div className="parent-container features-container">
        <div className="header">
          <span className="title">Features</span>
        </div>
        <div className="container-group">
          <List />
          <Entity id={this.props.match.params.id} />
        </div>
      </div>
    );
  }
}

export default withRouter(Features);
