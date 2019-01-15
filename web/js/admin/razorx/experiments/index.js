import { withRouter } from 'react-router-dom';
import { openModal, closeModal, notifyError } from 'common/modal';
import ExperimentsModal from './ExperimentsModal';
import { SwitchField } from 'ui/Field';
import List from './List';
import Entity from './Entity';

@withRouter
export default class Experiments extends React.PureComponent {
  showExperimentModal = _ => {
    openModal(<ExperimentsModal />);
  };

  showJSONModal = _ => {
    if (!window.CodeFlask) {
      notifyError('JSON Editor is missing. Reload page / check your Network!');
      return;
    }

    openModal(<ExperimentsModal JSONView />);
  };

  render() {
    return (
      <div class="parent-container experiments-container">
        <div class="header">
          Experiments
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
            <button class="btn btn--primary" onClick={this.showExperimentModal}>
              + Add New
            </button>
            <button
              class="btn btn--transparent json-btn"
              onClick={this.showJSONModal}
            >
              JSON
            </button>
          </div>
        </div>
        <div class="container-group">
          <List />
          <Entity id={this.props.id} />
        </div>
      </div>
    );
  }
}
