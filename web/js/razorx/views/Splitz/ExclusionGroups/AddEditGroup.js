import React from 'react';
import PropTypes from 'prop-types';
import { withRouter } from 'common/deprecated/withRouter';
import { ModalContent } from 'common/new-ui/Modal';
import { closeModal, notifySuccess, notifyError } from 'razorx/components/Modal';
import Form from 'razorx/components/ui/Form';
import Field, { TextAreaField, SearchableSelectField } from 'razorx/components/ui/Field';
import { splitzFetch } from 'razorx/helpers/fetch';

class AddEditGroup extends React.Component {
  state = {
    isSaving: false,
    isFetchingProjects: true,
    projects: [],
    selectedProject: null,
  };

  onSubmit = (form) => {
    const isInvalid = this.isInvalid();

    if (!!isInvalid) {
      notifyError(isInvalid);
      return;
    }

    const groupPayload = {
      group: {
        ...form,
        projectId: this.state.selectedProject.id,
      },
    };

    const groupUrl = 'exclusion_group.v1.ExclusionGroupAPI/Create';
    const successMsg = `Exclusion group is successfully created`;

    this.setState({ isSaving: true });

    splitzFetch({ url: groupUrl, data: groupPayload })
      .then((response) => {
        this.setState({ isSaving: false });
        notifySuccess(successMsg);
        closeModal();
        this.props.collection.fetch();
        this.props.history.push(`/splitz/groups/${response.group.id}`);
      })
      .catch((err) => {
        this.setState({ isSaving: false });
        notifyError(err);
      });
  };

  isInvalid() {
    if (!this.state.selectedProject || !this.state.selectedProject.id) {
      return 'Exclusion group should be associated with a project';
    }

    return false;
  }

  componentDidMount() {
    splitzFetch({
      url: 'project.v1.ProjectAPI/List',
      data: {
        limit: 100, // TODO: constraint
        offset: 0,
      },
    })
      .then((projectRes) => {
        this.setState({
          isFetchingProjects: false,
          projects: projectRes.items,
        });
      })
      .catch(() => {
        this.setState({
          isFetchingProjects: false,
        });
      });
  }

  handleSelectProject = ({ option }) => {
    this.setState({ selectedProject: option });
  };

  render() {
    const { isSaving, isFetchingProjects, projects, selectedProject } = this.state;
    const header = 'Create Exclusion Group';
    const isLoading = isSaving || isFetchingProjects;

    return (
      <ModalContent className="modal-features modal-json-edit" header={header}>
        <Form
          onSubmit={this.onSubmit}
          className="full-span full-elements"
          style={{ opacity: isLoading ? 0.5 : 1 }}
        >
          <React.Fragment>
            {isLoading && <div className="spinner center" />}
            <Field
              type="text"
              label="Name"
              name="name"
              placeholder="Exclusion Group Name"
              defaultValue=""
              required
            />
            <TextAreaField
              label="Description"
              name="description"
              placeholder="Exclusion Group Description"
              defaultValue=""
              required
            />
            <SearchableSelectField
              required
              name="project_id"
              optionComponent={({ option }) => (
                <div>
                  {option.name} - {option.id}
                </div>
              )}
              placeholder="Select a project"
              searchIndices={['id', 'name']}
              label="Project"
              trackBy="id"
              options={projects || []}
              selected={selectedProject}
              onChange={this.handleSelectProject}
            />
            <div style={{ marginTop: 24 }} />
          </React.Fragment>
          <div className="footer">
            <button className="btn btn--primary">
              Create Exclusion Group
              <span className="spin-btn" />
            </button>
          </div>
        </Form>
      </ModalContent>
    );
  }
}

AddEditGroup.defaultProps = {};

AddEditGroup.propTypes = {
  collection: PropTypes.object.isRequired,
  history: PropTypes.object.isRequired,
};

export default withRouter(AddEditGroup);
