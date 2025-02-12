import React from 'react';
import PropTypes from 'prop-types';
import { withRouter } from 'common/deprecated/withRouter';
import { ModalContent } from 'common/new-ui/Modal';
import { closeModal, notifySuccess, notifyError } from 'razorx/components/Modal';
import Form from 'razorx/components/ui/Form';
import Field, {
  SearchableSelectField,
  SelectField,
  TextAreaField,
} from 'razorx/components/ui/Field';
import { splitzFetch } from 'razorx/helpers/fetch';
import {
  CAPITAL_BU_LABEL,
  CAPITAL_BU_VALUE,
  PAYMENTS_BU_LABEL,
  PAYMENTS_BU_VALUE,
  PLATFORM_BU_LABEL,
  PLATFORM_BU_VALUE,
  PROJECT_LIST,
  RAZORPAYX_BU_LABEL,
  RAZORPAYX_BU_VALUE,
} from './constants';

class AddEditProject extends React.Component {
  state = {
    isSaving: false,
    isFetchingProjects: true,
    projects: [],
    team: null,
    errorMessage: null,
  };

  onSubmit = (form) => {
    const { slackChannel, ...formWithoutSlackChannel } = form;
    const projectPayload = {
      project: {
        ...formWithoutSlackChannel,
        pod: this.state.team,
        id: this.props.data.id,
        metadata: {
          slack_channel_id: form.slackChannel,
        },
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

  fetchProjectsByBU = (businessUnit, teamName) => {
    splitzFetch({
      url: PROJECT_LIST,
      data: { business_unit: businessUnit },
    })
      .then((projListResponse) => {
        const projects = projListResponse?.items || [];
        this.setState({ projects, isFetchingProjects: false, team: teamName });
      })
      .catch((err) => {
        this.setState({ projects: [], isFetchingProjects: false });
        notifyError(err);
      });
  };

  componentDidMount() {
    const { pod, business_unit } = this.props.data;
    this.fetchProjectsByBU(business_unit || 'platform', pod);
  }

  handleBUChange = (option) => {
    this.fetchProjectsByBU(option.target.value);
    this.setState({
      errorMessage: null,
    });
  };

  handleSelectTeam = (evt) => {
    const { id, name, business_unit, pod } = evt.option;
    this.setState({
      errorMessage: `Project with business unit '${business_unit}' and pod '${pod}' already exists. Project id: ${id}, name: ${name}. Please reuse this project for any new experiments of your team or set a different business unit/ team name combination.`,
    });
  };

  handleTeamNameChange = (option) => {
    this.setState({
      team: option.target.value,
    });
  };

  render() {
    const { data, isEdit } = this.props;
    const { isSaving, isFetchingProjects, projects, team, errorMessage } = this.state;
    const header = isEdit ? `Edit Project – ${data.id}` : 'Create Project';

    const buOptions = [
      {
        label: PLATFORM_BU_LABEL,
        value: PLATFORM_BU_VALUE,
      },
      {
        label: PAYMENTS_BU_LABEL,
        value: PAYMENTS_BU_VALUE,
      },
      {
        label: CAPITAL_BU_LABEL,
        value: CAPITAL_BU_VALUE,
      },
      {
        label: RAZORPAYX_BU_LABEL,
        value: RAZORPAYX_BU_VALUE,
      },
    ].map((op, i) => (
      <option key={i} value={op.value}>
        {op.label}
      </option>
    ));

    return (
      <ModalContent className="modal-features modal-json-edit" header={header}>
        {errorMessage && (
          <div className="sub-description whitelist-description">
            <i className="fa fa-exclamation-circle whitelist-description-text" />
            {errorMessage}
          </div>
        )}
        <Form
          onSubmit={this.onSubmit}
          className="full-span full-elements"
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
          <SelectField
            name="businessUnit"
            label="Business Unit"
            placeholder="Business Unit"
            defaultValue={isEdit ? data.business_unit : ''}
            onChange={this.handleBUChange}
            required
          >
            {buOptions}
          </SelectField>
          <SearchableSelectField
            disabled={isFetchingProjects}
            name="teamName"
            optionComponent={({ option }) => <div>{option.pod}</div>}
            searchIndices={['pod']}
            label="POD/Team Name"
            placeholder="POD/Team Name"
            trackBy="id"
            options={projects}
            onBlur={this.handleTeamNameChange}
            onChange={this.handleSelectTeam}
            selected={team}
            className="search-box"
            required
          />
          <Field
            type="text"
            label={
              <span>
                Slack Channel{' '}
                <i
                  className="i i-info-circle i-large"
                  title="Notifications related to auto termination will be sent on this channel"
                />
              </span>
            }
            name="slackChannel"
            placeholder="Enter Slack Channel ID"
            defaultValue={isEdit ? data.metadata?.slack_channel_id ?? '' : ''}
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

export default withRouter(AddEditProject);
