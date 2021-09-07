import React from 'react';
import PropTypes from 'prop-types';
import { withRouter, Link } from 'react-router-dom';
import AddEditProject from './AddEditProject';
import { openModal } from 'razorx/components/Modal';
import { formatDate } from 'razorx/helpers/utils';
import { splitzFetch } from 'razorx/helpers/fetch';
import ExperimentsModal from 'razorx/views/Experiments/Modal';

@withRouter
export default class ProjectDetails extends React.Component {
  state = {
    isFetchingProject: false,
    isFetchingEnvironments: false,
    isFetchingExperiments: false,
    data: null,
    environments: null,
    experiments: null,
  };

  componentDidMount() {
    this.fetch(this.props.projectId);
  }

  componentWillReceiveProps(nextProps) {
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
      isFetchingEnvironments: true,
      isFetchingExperiments: true,
      data: null,
      environments: null,
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
      url: 'environment.v1.EnvironmentAPI/List',
      data: {
        projectID: projectId,
      },
    })
      .then((resp) => {
        this.setState({
          isFetchingEnvironments: false,
        });

        if (resp) {
          this.setState({
            environments: resp.environments,
          });
        }
      })
      .catch(() => {
        this.setState({
          isFetchingEnvironments: false,
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
        environments={this.state.environments}
        onEdit={this.onEdit}
        isEdit
      />,
    );
  };

  onEdit = () => this.fetch(this.props.projectId);

  render() {
    const {
      isFetchingProject,
      isFetchingEnvironments,
      isFetchingExperiments,
      data,
      environments,
      experiments,
    } = this.state;
    const { projectId } = this.props;

    const isFetching = isFetchingProject || isFetchingEnvironments || isFetchingExperiments;
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
          <div>
            <div className="label">Environments</div>
            {environments && environments.length > 0 ? (
              environments.map((env) => (
                <div key={env.id}>
                  <span className="square-pills">{env.name}</span>
                </div>
              ))
            ) : (
              <div>No environments created for project</div>
            )}
          </div>

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
