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
      <div class="parent-container features-container">
        <div class="header">
          <span class="title">Features</span>
          <div class="btn-group">
            <button class="btn btn--primary" onClick={this.showFeatureModal}>
              + Add New
            </button>
          </div>
          <button class="btn btn--transparent raw-btn" onClick={this.showJSONModal}>
            RAW
          </button>
        </div>
        <div class="container-group">
          <List />
          <Entity id={this.props.match.params.id} />
        </div>
      </div>
    );
  }
}

export default withRouter(Features);
