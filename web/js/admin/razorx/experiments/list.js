export default class Experiments extends React.Component {
  showExperimentModal = _ => {};

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
