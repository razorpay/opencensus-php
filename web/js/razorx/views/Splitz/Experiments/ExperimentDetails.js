import React from 'react';
import PropTypes from 'prop-types';
import { withRouter, Link } from 'react-router-dom';
import AddEditExperiment from './AddEditExperiment';
import WhitelistExperiment from './WhitelistExperiment';
import * as experimentHelpers from './experimentHelpers';
import { openModal, notifyError } from 'razorx/components/Modal';
import { formatDate } from 'razorx/helpers/utils';
import { splitzFetch } from 'razorx/helpers/fetch';
import AsyncButton from 'razorx/components/ui/AsyncButton';
import ExperimentsModal from 'razorx/views/Experiments/Modal';
import { statusPill } from 'razorx/helpers/data';

@withRouter
export default class ExperimentDetails extends React.Component {
  state = {
    isFetchingExperiment: false,
    isFetchingProject: false,
    isFetchingExclusionGroup: false,
    data: null, // experiment
    project: null,
    exclusionGroup: null,
  };

  componentDidMount() {
    this.fetch(this.props.experimentId);
  }

  componentWillReceiveProps(nextProps) {
    if (this.props.experimentId !== nextProps.experimentId) {
      this.fetch(nextProps.experimentId);
    }
  }

  fetch(experimentId) {
    if (!experimentId) {
      return;
    }

    this.setState({
      isFetchingExperiment: true,
      isFetchingProject: true,
      isFetchingExclusionGroup: true,
      data: null,
      project: null,
      exclusionGroup: null,
    });

    splitzFetch({
      url: 'experiment.v1.ExperimentAPI/Get',
      data: {
        id: experimentId,
      },
    })
      .then((res) => {
        this.setState({
          isFetchingExperiment: false,
          data: res.experiment,
        });

        return Promise.all(
          [
            splitzFetch({
              url: 'project.v1.ProjectAPI/Get',
              data: {
                projectId: this.state.data.project_id,
              },
            }),
            this.state.data.exclusion_group && this.state.data.exclusion_group.entity_id
              ? splitzFetch({
                  url: 'exclusion_group.v1.ExclusionGroupAPI/Get',
                  data: {
                    id: this.state.data.exclusion_group.entity_id,
                  },
                })
              : null,
          ].filter(Boolean),
        );
      })
      .then((allResponses) => {
        const projectRes = allResponses[0];
        const exclusionGroupRes = allResponses[1];
        this.setState({
          isFetchingProject: false,
          isFetchingExclusionGroup: false,
          project: projectRes.project,
          exclusionGroup: exclusionGroupRes ? exclusionGroupRes.group : null,
        });
      })
      .catch((err) => {
        notifyError(err);
        this.setState({
          isFetchingExperiment: false,
          isFetchingProject: false,
          isFetchingExclusionGroup: false,
        });
      });
  }

  activateExperiment = () => {
    splitzFetch({
      url: 'experiment.v1.ExperimentAPI/Action',
      data: {
        id: this.state.data.id,
        status: 'activated',
      },
    }).then(() => {
      this.fetch(this.props.experimentId);
      this.props.collection.fetch();
    });
  };

  terminateExperiment = () => {
    splitzFetch({
      url: 'experiment.v1.ExperimentAPI/Action',
      data: {
        id: this.state.data.id,
        status: 'terminated',
      },
    });

    this.fetch(this.props.experimentId);
    this.props.collection.fetch();
  };

  showAddExperiment = () => openModal(<ExperimentsModal experimentId={this.state.data} />);

  showEditExperiment = () => {
    openModal(
      <AddEditExperiment
        collection={this.props.collection}
        data={this.state.data}
        onEdit={this.onEdit}
        isEdit
      />,
    );
  };

  showWhitelisting = () => {
    const experiment = this.state.data;
    const variantsWithWhitelisting = experiment.variants.map((variant) => ({
      ...variant,
      whitelistedIds: this.getWhitelistedIds(variant.id),
    }));

    openModal(
      <WhitelistExperiment
        data={this.state.data}
        variants={variantsWithWhitelisting}
        onEdit={this.onEdit}
      />,
    );
  };

  onEdit = () => this.fetch(this.props.experimentId);

  isEditable = () => {
    const experiment = this.state.data;
    if (experiment.status === 'terminated') {
      return false;
    }
    if (experiment.status === 'activated' && experiment.type === 'split') {
      return false;
    }
    return true;
  };

  getWhitelistedIds = (variantId) => {
    const { data } = this.state;

    if (!data.whitelisting || !data.whitelisting.length) {
      return [];
    }

    const whitelist = data.whitelisting.find((w) => w.entity_id === variantId);

    if (whitelist) {
      return whitelist.ids;
    }

    return [];
  };

  render() {
    const {
      isFetchingExperiment,
      isFetchingProject,
      isFetchingExclusionGroup,
      data,
      project,
      exclusionGroup,
    } = this.state;
    const { experimentId } = this.props;

    const isFetching = isFetchingExperiment || isFetchingProject || isFetchingExclusionGroup;
    let content;

    const type = {
      ramping: 'Ramp',
      split: 'A/B',
    };

    if (!experimentId) {
      content = null;
    } else if (isFetching) {
      content = <div className="spinner center" />;
    } else if (!isFetching && (!data || !project)) {
      content = (
        <div className="page-center empty-entity">
          <i className="i-layers" />
          <div className="description">
            <div>ID: {experimentId}</div>
            No Experiment found or some issue with data.
          </div>
        </div>
      );
    } else {
      const audienceRules = data.audience
        ? experimentHelpers.getAudienceRules(data.audience)
        : undefined;

      content = (
        <div className="entity-details">
          <div className="sub-description">
            <span>
              <b>ID:</b> {data.id}
            </span>
            <span className="to-right">
              {this.isEditable() ? (
                <a className="link text-bold" onClick={this.showEditExperiment}>
                  Edit Experiment
                </a>
              ) : null}
              <span
                style={{
                  fontSize: '25px',
                  position: 'absolute',
                  top: '0',
                  right: '15px',
                }}
                className="cross"
                onClick={this.props.hideDetails}
              />
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
          {statusPill(data.status)}
          <br />
          <br />
          {data.status === 'activated' ? (
            <div>
              <AsyncButton
                class="link info text-info text-bold"
                pendingClass="link info-faded text-info text-bold btn-pending"
                onClick={this.showWhitelisting}
              >
                <i className="fa fa-exclamation-circle" style={{ paddingRight: '5px' }} />
                Whitelist IDs
              </AsyncButton>
              <br />
              <div className="sub-description">
                Only for testing/debugging purpose, please use audience and segments for bucketing
              </div>
            </div>
          ) : null}
          <br />
          {data.status === 'created' || data.status === 'terminated' ? (
            <AsyncButton
              class="link danger text-danger text-bold"
              pendingClass="link danger-faded text-danger text-bold btn-pending"
              confirm={`Do you want to Activate Experiment id "${data.id}"?`}
              onClick={this.activateExperiment}
            >
              Activate Experiment
              <span className="dot-loader">.</span>
            </AsyncButton>
          ) : null}
          {data.status === 'activated' ? (
            <AsyncButton
              class="link danger text-danger text-bold"
              pendingClass="link danger-faded text-danger text-bold btn-pending"
              confirm={`Do you want to Terminate Experiment id "${data.id}"?`}
              onClick={this.terminateExperiment}
            >
              Terminate Experiment
              <span className="dot-loader">.</span>
            </AsyncButton>
          ) : null}
          <br />
          <br />
          <div className="flex-row" style={{ justifyContent: 'space-between' }}>
            <div className="flex-row-item">
              <div className="label">Type</div>
              <span className="square-pills label-semi-muted">{type[data.type]}</span>
            </div>
            <div className="flex-row-item">
              <div className="label">Sampling Percentage</div>
              <span className="square-pills label-semi-muted">{data.sampling_percentage}</span>
            </div>
          </div>
          <br />
          <br />
          <div className="flex-row" style={{ justifyContent: 'space-between' }}>
            <div className="flex-row-item">
              <div className="label">Project</div>
              <span className="square-pills label-semi-muted">{project.name}</span>
              <div className="sub-description column">
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
          <div>
            <div className="title" style={{ position: 'inherit', fontSize: '18px' }}>
              Variants
            </div>
            {data.variants.map((variant) => {
              const whitelistedIds = this.getWhitelistedIds(variant.id);
              return (
                <div className="segment pad-highlight" key={variant.id}>
                  <div>
                    <span className="square-pills label-semi-muted">{variant.name}</span>
                  </div>
                  {variant.variables ? (
                    <div className="sub-segment" style={{ paddingLeft: '13px' }}>
                      {variant.variables.map((variable, i) => (
                        <div key={i}>
                          <span className="label">{variable.key}: &nbsp;</span>
                          <span className="sub-segment-group">{variable.value}</span>
                        </div>
                      ))}
                    </div>
                  ) : null}
                  {whitelistedIds.length ? (
                    <div className="sub-segment" style={{ paddingLeft: '13px' }}>
                      <br />
                      <span className="label">Whitelisted IDs: &nbsp;</span>
                      {whitelistedIds.map((id, i) => (
                        <div key={i}>
                          <span className="sub-segment-group">{id}</span>
                        </div>
                      ))}
                    </div>
                  ) : null}
                  <br />
                </div>
              );
            })}
          </div>
          {audienceRules && audienceRules.rules.length && audienceRules.rules[0].operator ? (
            <React.Fragment>
              <br />
              <br />
              <div className="pad-highlight">
                <div className="title" style={{ position: 'inherit', fontSize: '18px' }}>
                  Audience Rules
                </div>
                {audienceRules.rules.map((rule, i) => (
                  <div key={i}>
                    <div className="flex-row">
                      <div>&nbsp;{rule.key}</div>
                      <div>
                        <b>&nbsp;{experimentHelpers.ruleOperatorMap[rule.operator]}&nbsp;</b>
                      </div>
                      {['belongsTo', 'doesNotBelongTo'].includes(rule.operator) ? (
                        <div
                          className="link"
                          style={{ marginTop: '3px' }}
                          onClick={() => this.props.history.push(`/splitz/segments/${rule.value}`)}
                        >
                          {rule.value}
                        </div>
                      ) : (
                        <div>{experimentHelpers.stringifyNull(rule.value)}</div>
                      )}
                    </div>
                    {i < audienceRules.rules.length - 1 && (
                      <div className="flex-row">
                        <span
                          style={{ marginTop: '4px' }}
                          className="square-pills label-semi-muted"
                        >
                          {audienceRules.ruleCondition}
                        </span>
                      </div>
                    )}
                  </div>
                ))}
              </div>
            </React.Fragment>
          ) : null}
          {exclusionGroup && (
            <React.Fragment>
              <br />
              <br />
              <div className="flex-row">
                <div className="flex-row-item">
                  <div className="label">Exclusion Group</div>
                  <div className="sub-description column">
                    <div style={{ color: '#675a5d' }}>{exclusionGroup.name}</div>
                    <div style={{ color: 'black' }}>
                      Value / Weightage:{' '}
                      <span className="square-pills label-semi-muted">
                        {data.exclusion_group.value}
                      </span>
                    </div>
                    <div>
                      <b>ID: </b> {exclusionGroup.id}
                    </div>
                  </div>
                  <Link class="link" to={`/splitz/groups/${exclusionGroup.id}`}>
                    View Exclusion Group
                  </Link>
                </div>
                {/* <div className="flex-row-item">
              <div style={{ color: '#675a5d' }}>Value</div>
              <span className="square-pills label-semi-muted">{data.exclusion_group.value}</span>
            </div> */}
              </div>
            </React.Fragment>
          )}
          <br />
          <br />
        </div>
      );
    }

    return <div className="entity-container">{content}</div>;
  }
}

ExperimentDetails.propTypes = {
  experimentId: PropTypes.string.isRequired,
  collection: PropTypes.object.isRequired,
  hideDetails: PropTypes.func.isRequired,
};
