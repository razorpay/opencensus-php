import { openModal, closeModal, confirm } from 'common/modal';
import ExperimentsModal from './ExperimentsModal';

export default class Experiments extends React.Component {
  showExperimentModal = _ => {
    openModal(<ExperimentsModal />);
  };

  render() {
    return (
      <div>
        <div class="header">
          Experiments
          <div class="btn-group">
            <button
              class="btn btn--primary btn--round"
              onClick={this.showExperimentModal}
            >
              + Add New
            </button>
          </div>
        </div>
      </div>
    );
  }
}
