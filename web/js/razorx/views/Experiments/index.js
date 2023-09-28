import { withRouter } from 'common/deprecated/withRouter';
import { observer } from 'mobx-react';
import { getURLQueryParams } from 'common/utils/rzp-utils';
import { openModal, notifyError } from 'razorx/components/Modal';
import ExperimentsModal from './Modal';
import { SwitchField } from 'razorx/components/ui/Field';
import List from './List';
import Entity from './Entity';

import { AppStore } from 'razorx/store';
import React from 'react';

@observer
class Experiments extends React.Component {
  showExperimentModal = (_) => {
    openModal(<ExperimentsModal />);
  };

  showJSONModal = (_) => {
    if (!window.CodeFlask) {
      notifyError('JSON Editor is missing. Reload page / check your Network!');
      return;
    }

    openModal(<ExperimentsModal JSONView />);
  };

  render() {
    const queryParams = getURLQueryParams(this.props.location.search);

    return (
      <div class="parent-container experiments-container">
        <div class="header">
          <span class="title">Experiments</span>
          <SwitchField
            name="mode"
            defaultValue={AppStore.mode}
            disabledLabel="Test"
            enabledLabel="Live"
            enabledValue="live"
            disabledValue="test"
            onChange={AppStore.updateMode}
          />
          <div class="btn-group">
            <button class="btn btn--primary" onClick={this.showExperimentModal}>
              + Add New
            </button>
            <button class="btn btn--transparent raw-btn" onClick={this.showJSONModal}>
              RAW
            </button>
          </div>
        </div>
        <div class="container-group">
          <List
            mode={AppStore.mode}
            queryParams={{
              feature_id: queryParams.feature_id,
              status: queryParams.status,
            }}
          />
          <Entity id={this.props.match.params.id} />
        </div>
      </div>
    );
  }
}

export default withRouter(Experiments);
