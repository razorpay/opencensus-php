import React from 'react';
import PropTypes from 'prop-types';
import { Link } from 'react-router-dom';
import { withRouter } from 'common/deprecated/withRouter';
import AddEditProject from './AddEditProject';
import { openModal } from 'razorx/components/Modal';
import { formatDate } from 'razorx/helpers/utils';
import { splitzFetch } from 'razorx/helpers/fetch';
import ExperimentsModal from 'razorx/views/Experiments/Modal';

class ProjectDetails extends React.Component {
  state = {
    isFetchingProject: false,
    isFetchingExperiments: false,
    data: null,
    experiments: null,
  };

  componentDidMount() {
    this.fetch(this.props.projectId);
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    if (this.props.projectId !== nextProps.projectId) {
      this.fetch(nextProps.projectId);
    }
  }

  fetch(projectId) {
    if (!projectId) {
      return;
    }

    this.setState({
      isFetchingProject: true,
      isFetchingExperiments: true,
      data: null,
      experiments: null,
    });

    splitzFetch({
      url: 'project.v1.ProjectAPI/Get',
      data: {
        projectId,
      },
    })
      .then((resp) => {
        this.setState({
          isFetchingProject: false,
        });

        if (resp) {
          this.setState({
            data: resp.project,
          });
        }
      })
      .catch(() => {
        this.setState({
          isFetchingProject: false,
        });
      });

    splitzFetch({
      url: 'experiment.v1.ExperimentAPI/List',
      data: {
        projectId,
        limit: 1000,
        offset: 0,
      },
    })
      .then((resp) => {
        this.experimentsActiveCount = 0;
        this.setState({
          isFetchingExperiments: false,
        });
        if (resp) {
          const experiments = resp.items || [];
          this.experimentsActiveCount = experiments.reduce(
            (activeCount, experiment) =>
              experiment.status === 'activated' ? activeCount + 1 : activeCount,
            0,
          );
          this.setState({
            experiments,
          });
        }
      })
      .catch(() => {
        this.setState({
          isFetchingExperiments: false,
        });
      });
  }

  showAddExperiment = () => openModal(<ExperimentsModal projectId={this.state.data} />);

  showEditProject = () => {
    openModal(
      <AddEditProject
        collection={this.props.collection}
        data={this.state.data}
        onEdit={this.onEdit}
        isEdit
      />,
    );
  };

  onEdit = () => this.fetch(this.props.projectId);

  render() {
    const { isFetchingProject, isFetchingExperiments, data, experiments } = this.state;
    const { projectId } = this.props;

    const isFetching = isFetchingProject || isFetchingExperiments;
    let content;

    if (!projectId) {
      content = null;
    } else if (isFetching) {
      content = <div className="spinner center" />;
    } else if (!isFetching && !data) {
      content = (
        <div className="page-center empty-entity">
          <i className="i-layers" />
          <div className="description">
            <div>ID: {projectId}</div>
            No Project found!
          </div>
        </div>
      );
    } else {
      content = (
        <div className="entity-details">
          <div className="sub-description">
            <span>
              <b>ID:</b> {data.id}
            </span>
            <span className="to-right">
              <a className="link text-bold" onClick={this.showEditProject}>
                Edit Project
              </a>
            </span>
          </div>

          <div className="pad-highlight">
            <div className="title">{data.name}</div>
            <div className="description">
              {data.description}
              <div className="sub-description">
                <b>Created at</b> {formatDate(data.created_at)}
              </div>
            </div>
          </div>
          <br />
          <br />

          <div className="flex-row">
            <div className="flex-row-item">
              <div className="label">Business Unit</div>
            </div>
          </div>
          <div>{data.business_unit}</div>
          <br />

          <div className="flex-row">
            <div className="flex-row-item">
              <div className="label">POD/Team Name</div>
            </div>
          </div>
          <div>{data.pod}</div>
          <br />

          <div className="label">Experiments</div>
          {experiments && (
            <React.Fragment>
              <div>
                {this.experimentsActiveCount ? (
                  <div>
                    <div className="sub-description column">
                      <div>
                        <b>ACTIVE: {this.experimentsActiveCount} </b>
                        <br />
                        <Link class="link" to={`/splitz/experiments?project_id=${data.id}`}>
                          View all experiments
                        </Link>
                      </div>
                    </div>
                  </div>
                ) : (
                  <div className="sub-description column">
                    <b>No active experiment</b>
                  </div>
                )}
              </div>
              <br />
            </React.Fragment>
          )}
        </div>
      );
    }

    return <div className="entity-container">{content}</div>;
  }
}

ProjectDetails.propTypes = {
  projectId: PropTypes.string.isRequired,
  collection: PropTypes.object.isRequired,
};

export default withRouter(ProjectDetails);
