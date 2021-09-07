import React from 'react';
import PropTypes from 'prop-types';
import { withRouter } from 'react-router-dom';
import { ModalContent } from 'common/new-ui/Modal';
import EnumList from 'common/new-ui/Input/EnumList';
import { closeModal, notifySuccess, notifyError } from 'razorx/components/Modal';
import Form from 'razorx/components/ui/Form';
import Field, { TextAreaField } from 'razorx/components/ui/Field';
import { splitzFetch } from 'razorx/helpers/fetch';

@withRouter
export default class AddEditProject extends React.Component {
  state = {
    isSaving: false,
    environments: [],
  };

  onSubmit = (form) => {
    const isInvalid = this.isInvalid();

    if (!!isInvalid) {
      notifyError(isInvalid);
      return;
    }

    const projectPayload = {
      project: {
        ...form,
        id: this.props.data.id,
      },
    };

    let projectUrl = 'project.v1.ProjectAPI/';
    const environmentUrl = 'environment.v1.EnvironmentAPI/Create';
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
      .then((projectResponse) =>
        Promise.all([
          projectResponse,
          // add / update environments
          ...this.state.environments.map((env) =>
            splitzFetch({
              url: environmentUrl,
              data: {
                environment: {
                  name: env,
                  description: env,
                  projectID: projectResponse.project.id,
                },
              },
            }),
          ),
        ]),
      )
      .then((allResponses) => {
        const projectResponse = allResponses[0];
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

  isInvalid() {
    const existingEnvironments = this.props.environments.map((env) => env.name);
    const newEnvironments = this.state.environments;
    const environments = [...existingEnvironments, ...newEnvironments].filter(Boolean);

    if (!environments.length || !environments[0]) {
      return 'At least 1 environment must be added';
    }

    if (newEnvironments.some((env) => env.length < 3)) {
      return 'Environment name should be greater than 3 characters';
    }

    const trimmedEnvironments = environments.filter((v, i) => environments.indexOf(v) === i);
    if (trimmedEnvironments.length !== environments.length) {
      return 'Duplicate environments in the list';
    }

    return false;
  }

  onChangeEnumList = (enumList = []) => {
    let trimmedEnums = enumList.concat();

    trimmedEnums = trimmedEnums.reduce((r, o) => {
      if (o) {
        r.push(o);
      }

      return r;
    }, []);

    this.setState({ environments: trimmedEnums });
  };

  render() {
    const { data, environments: existingEnvironments = [], isEdit } = this.props;
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
          <div className="sub-heading">Environments*</div>
          {existingEnvironments.length > 0
            ? existingEnvironments.map((env) => (
                <div key={env.id} style={{ marginBottom: '7px' }}>
                  <span className="square-pills size-m">{env.name}</span>
                </div>
              ))
            : null}
          <EnumList
            class="variants-list"
            onChange={this.onChangeEnumList}
            addNewBtn={() => (
              <button type="button" className="btn btn--pill">
                <i className="i i-return-key" /> Add Environments
              </button>
            )}
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
  environments: [],
  data: {},
  onEdit: () => {},
  isEdit: false,
};

AddEditProject.propTypes = {
  collection: PropTypes.object.isRequired,
  history: PropTypes.object.isRequired,
  environments: PropTypes.array,
  data: PropTypes.object,
  onEdit: PropTypes.func,
  isEdit: PropTypes.bool,
};
