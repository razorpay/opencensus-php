export default class FeaturesList extends React.Component {
  showFeaturesModal = _ => {};

  render() {
    return (
      <div>
        <div class="header">
          Features
          <div class="btn-group">
            <button
              class="btn btn--primary btn--round"
              onClick={this.showFeaturesModal}
            >
              + Add New
            </button>
          </div>
        </div>
      </div>
    );
  }
}
