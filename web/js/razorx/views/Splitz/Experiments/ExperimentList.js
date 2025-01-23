import React from 'react';
import PropTypes from 'prop-types';
import { Link } from 'react-router-dom';
import { withRouter } from 'common/deprecated/withRouter';
import { formatDate } from 'razorx/helpers/utils';
import { PageTable } from 'razorx/components/ui/Table';
import Form from 'razorx/components/ui/Form';
import { SearchableSelectField } from 'razorx/components/ui/Field';
import { statusPill } from 'razorx/helpers/data';
import { splitzFetch } from 'razorx/helpers/fetch';
import { notifyError } from 'razorx/components/Modal';
// @observer

class ExperimentList extends React.Component {
  state = {
    isFetchingProjects: true,
    projects: [],
    selectedProject: null,
    selectedExperimentId: null,
    selectedExperimentName: null,
  };

  resetFilters = (e) => {
    const { collection } = this.props;
    // Clean filters in collection
    collection.resetFilters();

    this.setState({
      selectedProject: null,
    });

    // Clear filters in UI form
    const form = e.currentTarget.closest('form');
    form.reset();

    collection.fetch();
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
    splitzFetch({
      url: 'project.v1.ProjectAPI/List',
      data: {
        limit: 100,
        offset: 0,
      },
    })
      .then((projectRes) => {
        const { location } = this.props;

        this.setState({
          isFetchingProjects: false,
          projects: projectRes.items,
        });

        const urlParams = new URLSearchParams(location.search);
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
        () => this.filterList(),
      );
      return;
    }

    this.setState(
      {
        selectedProject: project,
      },
      () => this.filterList(),
    );
  };

  handleSelectInput = (e) => {
    const { name, value } = e?.target;
    this.setState({ [name]: value.trim() });
  };

  render() {
    const { isFetchingProjects, projects, selectedProject } = this.state;

    const { collection, showDetails } = this.props;

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
          <div className="input-search-box">
            <label className="input-search-label">Experiment ID</label>
            <input
              type="text"
              name="selectedExperimentId"
              className="search-box input-search-box"
              onChange={this.handleSelectInput}
            />
          </div>
          <div className="input-search-box">
            <label className="input-search-label">Experiment Name</label>
            <input
              type="text"
              name="selectedExperimentName"
              className="search-box input-search-box"
              onChange={this.handleSelectInput}
            />
          </div>
          <button className="btn btn--primary field">Search</button>
          <button type="button" className="btn btn--link field" onClick={this.resetFilters}>
            Clear
          </button>
        </Form>
        <div>
          <PageTable
            model={collection}
            fields={[
              ['ID', (item) => item.id],
              [
                'Name',
                (item) => (
                  <div className="flex-column">
                    <span>{item.name}</span>
                    {item.auto_terminate_at && item.to_be_terminated && (
                      <div className="pill label-danger">
                        Auto Termination:
                        {formatDate(item.auto_terminate_at)}
                      </div>
                    )}
                  </div>
                ),
              ],
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
            onClick={showDetails}
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

export default withRouter(ExperimentList);
