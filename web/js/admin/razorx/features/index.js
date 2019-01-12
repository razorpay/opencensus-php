import { openModal, closeModal, confirm } from 'common/modal';
import FeaturesModal from './FeaturesModal';
import { SwitchField } from 'ui/Field';
import List from './List';
import Entity from './Entity';

export default class Features extends React.PureComponent {
  showFeatureModal = _ => {
    openModal(<FeaturesModal />);
  };

  showJSONModal = _ => {
    openModal(<FeaturesModal JSONView />);
  };

  render() {
    return (
      <div class="parent-container features-container">
        <div class="header">
          Features
          <SwitchField
            name="mode"
            defaultValue="live"
            disabledLabel="Test"
            enabledLabel="Live"
            enabledValue="live"
            disabledValue="test"
            onChange={this.props.updateModeInStore}
          />
          <div class="btn-group">
            <button class="btn btn--primary" onClick={this.showFeatureModal}>
              + Add New
            </button>
          </div>
          <button
            class="btn btn--transparent json-btn"
            onClick={this.showJSONModal}
          >
            JSON
          </button>
        </div>
        <div class="container-group">
          <List />
          <Entity id={this.props.id} />
        </div>
      </div>
    );
  }
}
