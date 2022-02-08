import React from 'react';
import PropTypes from 'prop-types';
import { withRouter } from 'react-router-dom';
import { ModalContent } from 'common/new-ui/Modal';
import { closeModal, notifySuccess, notifyError } from 'razorx/components/Modal';
import Form from 'razorx/components/ui/Form';
import Field, { TextAreaField } from 'razorx/components/ui/Field';
import { splitzFetch } from 'razorx/helpers/fetch';

@withRouter
export default class AddEditProject extends React.Component {
  state = {
    isSaving: false,
  };

  onSubmit = (form) => {
    const projectPayload = {
      project: {
        ...form,
        id: this.props.data.id,
      },
    };

    let projectUrl = 'project.v1.ProjectAPI/';
    let successMsg = '';

    if (this.props.isEdit) {
      projectUrl += 'Update';
      successMsg = `Project ${this.props.data.id} is successfully updated`;
    } else {
      projectUrl += 'Create';
      successMsg = 'Project is successfully created';
    }

    this.setState({ isSaving: true });

    // add / update project
    splitzFetch({ url: projectUrl, data: projectPayload })
      .then((projectResponse) => {
        this.setState({ isSaving: false });
        notifySuccess(successMsg);
        closeModal();

        this.props.collection.fetch();

        if (this.props.isEdit) {
          this.props.onEdit();
        } else {
          this.props.history.push(`/splitz/projects/${projectResponse.project.id}`);
        }
      })
      .catch((err) => {
        this.setState({ isSaving: false });
        notifyError(err);
      });
  };

  render() {
    const { data, isEdit } = this.props;
    const { isSaving } = this.state;
    const header = isEdit ? `Edit Project – ${data.id}` : 'Create Project';

    return (
      <ModalContent class="modal-features modal-json-edit" header={header}>
        <Form
          onSubmit={this.onSubmit}
          class="full-span full-elements"
          style={{ opacity: isSaving ? 0.5 : 1 }}
        >
          {isSaving && <div className="spinner center" />}
          <Field
            type="text"
            label="Name"
            name="name"
            placeholder="Project Name"
            defaultValue={isEdit ? data.name : ''}
            required
          />
          <TextAreaField
            label="Description"
            name="description"
            placeholder="Project Description"
            defaultValue={isEdit ? data.description : ''}
            required
          />
          <div style={{ marginTop: 24 }} />
          <div className="footer">
            <button className="btn btn--primary">
              {isEdit ? 'Update' : 'Create'} Project
              <span className="spin-btn" />
            </button>
          </div>
        </Form>
      </ModalContent>
    );
  }
}

AddEditProject.defaultProps = {
  data: {},
  onEdit: () => {},
  isEdit: false,
};

AddEditProject.propTypes = {
  collection: PropTypes.object.isRequired,
  history: PropTypes.object.isRequired,
  data: PropTypes.object,
  onEdit: PropTypes.func,
  isEdit: PropTypes.bool,
};
