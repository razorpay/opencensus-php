import React from 'react';
import PropTypes from 'prop-types';
import { withRouter, Link } from 'react-router-dom';
import { formatDate } from 'razorx/helpers/utils';
import { PageTable } from 'razorx/components/ui/Table';
import Form from 'razorx/components/ui/Form';
import { SearchableSelectField } from 'razorx/components/ui/Field';
import { statusPill } from 'razorx/helpers/data';
import { splitzFetch } from 'razorx/helpers/fetch';

// @observer
@withRouter
export default class ExperimentList extends React.Component {
  state = {
    isFetchingProjects: true,
    projects: [],
    selectedProject: null,
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
    const { selectedProject } = this.state;
    this.props.collection.applyFilters({
      projectId: selectedProject ? selectedProject.id : '',
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
        this.setState({
          isFetchingProjects: false,
          projects: projectRes.items,
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
      .catch(() => {
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

  render() {
    const { isFetchingProjects, projects, selectedProject } = this.state;

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
            style={{ width: '250px' }}
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
