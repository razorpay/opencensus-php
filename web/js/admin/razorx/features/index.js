import { observer } from 'mobx-react';
import { openModal, closeModal, notifyError } from 'common/modal';
import FeaturesModal from './FeaturesModal';
import { SwitchField } from 'ui/Field';
import List from './List';
import Entity from './Entity';
import { AppStore } from 'admin/user';

@observer
export default class Features extends React.Component {
  showFeatureModal = _ => {
    openModal(<FeaturesModal />);
  };

  showJSONModal = _ => {
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
            <button class="btn btn--primary" onClick={this.showFeatureModal}>
              + Add New
            </button>
          </div>
          <button
            class="btn btn--transparent raw-btn"
            onClick={this.showJSONModal}
          >
            RAW
          </button>
        </div>
        <div class="container-group">
          <List mode={AppStore.mode} />
          <Entity id={this.props.id} />
        </div>
      </div>
    );
  }
}
