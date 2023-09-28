import React from 'react';
import PropTypes from 'prop-types';
import { withRouter } from 'common/deprecated/withRouter';
import { observer } from 'mobx-react';
import { openModal } from 'razorx/components/Modal';
import Collection from 'razorx/model/collection';
import { splitzFetch } from 'razorx/helpers/fetch';
import AddEditExperiment from './AddEditExperiment';
import ExperimentList from './ExperimentList';
import ExperimentDetails from './ExperimentDetails';

@observer
class Experiments extends React.Component {
  state = {
    shouldShowDetails: !!this.props.match.params.id,
  };

  collection = new Collection({
    isSplitz: true,
    fetchFn: splitzFetch,
    data: {
      url: 'experiment.v1.ExperimentAPI/List',
    },
  });

  showAddEditExperiment = () => openModal(<AddEditExperiment collection={this.collection} />);

  showDetails = () => this.setState({ shouldShowDetails: true });

  hideDetails = () => this.setState({ shouldShowDetails: false });

  render() {
    const { shouldShowDetails } = this.state;
    const experimentId = this.props.match.params.id;

    return (
      <div className="parent-container features-container">
        <div className="header">
          <span className="title">Experiments</span>
          <div className="btn-group">
            <button className="btn btn--primary" onClick={this.showAddEditExperiment}>
              + New Experiment
            </button>
          </div>
        </div>
        <div className="container-group">
          <ExperimentList collection={this.collection} showDetails={this.showDetails} />
          {shouldShowDetails && experimentId ? (
            <ExperimentDetails
              experimentId={experimentId}
              collection={this.collection}
              hideDetails={this.hideDetails}
            />
          ) : null}
        </div>
      </div>
    );
  }
}

Experiments.propTypes = {
  match: PropTypes.object.isRequired,
};

export default withRouter(Experiments);
