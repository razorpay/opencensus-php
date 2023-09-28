import React from 'react';
import PropTypes from 'prop-types';
import { Link } from 'react-router-dom';
import { withRouter } from 'common/deprecated/withRouter';
import { openModal } from 'razorx/components/Modal';
import { formatDate } from 'razorx/helpers/utils';
import { splitzFetch } from 'razorx/helpers/fetch';
import ExperimentsModal from 'razorx/views/Experiments/Modal';

class GroupDetails extends React.Component {
  state = {
    isFetchingGroup: false,
    isFetchingProject: false,
    data: null,
    project: null,
    experiments: [],
  };

  componentDidMount() {
    this.fetch(this.props.groupId);
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    if (this.props.groupId !== nextProps.groupId) {
      this.fetch(nextProps.groupId);
    }
  }

  fetch(groupId) {
    if (!groupId) {
      return;
    }

    this.setState({
      isFetchingGroup: true,
      isFetchingProject: true,
      data: null,
      project: null,
    });

    splitzFetch({
      url: 'exclusion_group.v1.ExclusionGroupAPI/Get',
      data: {
        id: groupId,
      },
    })
      .then((groupRes) => {
        this.setState({
          isFetchingGroup: false,
          data: groupRes.group,
        });

        let experimentsFetch = [];

        if (groupRes.group.traffic_allocation) {
          experimentsFetch = groupRes.group.traffic_allocation.map((bucket) =>
            splitzFetch({
              url: 'experiment.v1.ExperimentAPI/Get',
              data: {
                id: bucket.entity_id,
              },
            }),
          );
        }

        return Promise.all([
          splitzFetch({
            url: 'project.v1.ProjectAPI/Get',
            data: {
              projectId: groupRes.group.project_id,
            },
          }),
          ...experimentsFetch,
        ]);
      })
      .then((allResponses) => {
        const [projectRes, ...experimentRes] = allResponses;

        this.setState({
          isFetchingProject: false,
          project: projectRes.project,
          experiments: experimentRes.map((res) => res.experiment),
        });
      })
      .catch(() => {
        this.setState({
          isFetchingGroup: false,
          isFetchingProject: false,
        });
      });
  }

  showAddExperiment = () => openModal(<ExperimentsModal groupId={this.state.data} />);

  onEdit = () => this.fetch(this.props.groupId);

  render() {
    const { isFetchingGroup, isFetchingProject, data, project, experiments } = this.state;
    const { groupId } = this.props;

    const isFetching = isFetchingGroup || isFetchingProject;
    let content;

    if (!groupId) {
      content = null;
    } else if (isFetching) {
      content = <div className="spinner center" />;
    } else if (!isFetching && !data) {
      content = (
        <div className="page-center empty-entity">
          <i className="i-layers" />
          <div className="description">
            <div>ID: {groupId}</div>
            No Exclusion Group found!
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
          </div>
          <div className="pad-highlight">
            <div className="title">{data.name}</div>
            <div className="description">
              {data.description}
              <div className="sub-description">
                <b>Created at</b> {formatDate(data.created_at)}
              </div>
            </div>
            <br />
            <br />
          </div>
          <br />
          <div className="flex-row">
            <div className="flex-row-item">
              <div className="label">Project</div>
              <div className="sub-description column">
                <div style={{ color: '#675a5d' }}>{project.name}</div>
                <div>
                  <b>ID: </b> {project.id}
                </div>
              </div>
              <Link class="link" to={`/splitz/projects/${project.id}`}>
                View Project
              </Link>
            </div>
          </div>
          <br />
          <br />
          <div className="label">Traffic Allocation</div>
          {data.traffic_allocation ? (
            data.traffic_allocation.map((experiment) => (
              <div className="flex-row" key={experiment.entity_id}>
                <div className="flex-row-item">
                  <div className="sub-description column">
                    <div style={{ color: '#675a5d' }}>
                      {experiments.find((exp) => exp.id === experiment.entity_id).name}
                    </div>
                    <div>
                      <b>{experiment.entity_id}</b>
                    </div>
                  </div>
                  <Link class="link" to={`/splitz/experiments/${experiment.entity_id}`}>
                    View Experiment
                  </Link>
                </div>
                <div
                  className="flex-row-item"
                  style={{ marginLeft: 'auto', paddingRight: '30px', paddingBottom: '35px' }}
                >
                  <div style={{ color: '#675a5d', fontSize: '12px' }}>Percentage %</div>
                  <span className="square-pills label-semi-muted">{experiment.value}</span>
                </div>
              </div>
            ))
          ) : (
            <div style={{ color: '#675a5d' }}>No traffic allocation yet.</div>
          )}
        </div>
      );
    }

    return <div className="entity-container">{content}</div>;
  }
}

GroupDetails.propTypes = {
  groupId: PropTypes.string.isRequired,
};

export default withRouter(GroupDetails);
