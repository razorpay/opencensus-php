import { openModal, closeModal, confirm } from 'common/modal';
import FeaturesModal from './FeaturesModal';

export default class FeaturesList extends React.Component {
  showExperimentModal = _ => {
    openModal(<FeaturesModal />);
  };

  render() {
    return (
      <div>
        <div class="header">
          Features
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
