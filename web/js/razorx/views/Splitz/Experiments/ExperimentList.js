import React from 'react';
import PropTypes from 'prop-types';
import { withRouter, Link } from 'react-router-dom';
import { formatDate } from 'razorx/helpers/utils';
import { PageTable } from 'razorx/components/ui/Table';
import Form from 'razorx/components/ui/Form';
import { SearchableSelectField } from 'razorx/components/ui/Field';
import { statusPill } from 'razorx/helpers/data';
import { splitzFetch } from 'razorx/helpers/fetch';
import { notifyError } from 'razorx/components/Modal';

// @observer
@withRouter
export default class ExperimentList extends React.Component {
  state = {
    isFetchingProjects: true,
    projects: [],
    selectedProject: null,
    experiments: null,
    selectedExperimentId: null,
    selectedExperimentName: null,
  };

  resetFilters = (e) => {
    // Clean filters in collection
    this.props.collection.resetFilters();

    this.setState({
      selectedProject: null,
    });

    // Clear filters in UI form
    const form = e.currentTarget.closest('form');
    form.reset();

    this.props.collection.fetch();
  };

  filterList = () => {
    const { selectedProject, selectedExperimentId, selectedExperimentName } = this.state;

    this.props.collection.applyFilters({
      projectId: selectedProject ? selectedProject.id : '',
      id: selectedExperimentId || '',
      name: selectedExperimentName || '',
    });
  };

  componentDidMount() {
    Promise.all([
      splitzFetch({
        url: 'project.v1.ProjectAPI/List',
        data: {
          limit: 100,
          offset: 0,
        },
      }),
      splitzFetch({
        url: 'experiment.v1.ExperimentAPI/List',
        data: {
          limit: 100,
          offset: 0,
        },
      }),
    ])
      .then((allResponses) => {
        const [projectRes, experimentRes] = allResponses;

        this.setState({
          isFetchingProjects: false,
          projects: projectRes.items,
          experiments: experimentRes.items,
        });

        const urlParams = new URLSearchParams(this.props.location.search);
        const projectId = urlParams.get('project_id');

        if (projectId) {
          const currentProject = projectRes.items.find((project) => project.id === projectId);

          this.handleSelectProject({
            option: currentProject,
          });
        }
      })
      .catch((err) => {
        notifyError(err);
        this.setState({
          isFetchingProjects: false,
        });
      });
  }

  handleSelectProject = ({ option: project }) => {
    if (!project) {
      this.setState(
        {
          selectedProject: null,
        },
        this.filterList(),
      );
      return;
    }

    this.setState(
      {
        selectedProject: project,
      },
      this.filterList(),
    );
  };

  handleSelectExperimentId = ({ option }) => {
    if (!option) {
      this.setState(
        {
          selectedExperimentId: null,
        },
        this.filterList(),
      );
      return;
    }

    this.setState(
      {
        selectedExperimentId: option.id,
      },
      this.filterList(),
    );
  };

  handleSelectExperimentName = ({ option }) => {
    if (!option) {
      this.setState(
        {
          selectedExperimentName: null,
        },
        this.filterList(),
      );
      return;
    }

    this.setState(
      {
        selectedExperimentName: option.name,
      },
      () => this.filterList(),
    );
  };

  render() {
    const {
      isFetchingProjects,
      projects,
      selectedProject,
      experiments,
      selectedExperimentId,
      selectedExperimentName,
    } = this.state;

    return (
      <div className="list-container">
        <Form onSubmit={this.filterList} class="filters">
          <SearchableSelectField
            disabled={isFetchingProjects}
            name="project_id"
            optionComponent={({ option }) => (
              <div>
                {option.name} - {option.id}
              </div>
            )}
            placeholder="Select a project"
            searchIndices={['id', 'name']}
            label="Select Project"
            trackBy="id"
            options={projects || []}
            selected={selectedProject}
            onChange={this.handleSelectProject}
            className="search-box"
          />
          <SearchableSelectField
            name="experiment_id"
            optionComponent={(experiment) => <div>{experiment?.option?.id}</div>}
            placeholder="Select an experiment ID"
            searchIndices={['id']}
            label="Select Experiment ID"
            options={experiments || []}
            selected={selectedExperimentId}
            onChange={this.handleSelectExperimentId}
            className="search-box"
          />
          <SearchableSelectField
            name="experiment_name"
            optionComponent={(experiment) => <div>{experiment?.option?.name}</div>}
            placeholder="Select an experiment name"
            searchIndices={['name']}
            label="Select Experiment Name"
            options={experiments || []}
            selected={selectedExperimentName}
            onChange={this.handleSelectExperimentName}
            className="search-box"
          />
          <button className="btn btn--primary field">Search</button>
          <button type="button" className="btn btn--link field" onClick={this.resetFilters}>
            Clear
          </button>
        </Form>
        <div>
          <PageTable
            model={this.props.collection}
            fields={[
              ['ID', (item) => item.id],
              ['Name', (item) => item.name],
              ['Status', (item) => statusPill(item.status)],
              [
                'Project ID',
                (item) => (
                  <object>
                    <Link to={`/splitz/projects/${item.project_id}`}>
                      <span className="link">{item.project_id}</span>
                    </Link>
                  </object>
                ),
              ],
              ['Created On', (item) => formatDate(item.created_at)],
            ]}
            href={(item) => `/splitz/experiments/${item.id}`}
            info={false}
            onClick={() => this.props.showDetails()}
          />
        </div>
      </div>
    );
  }
}

ExperimentList.propTypes = {
  collection: PropTypes.object.isRequired,
  showDetails: PropTypes.func.isRequired,
};
