import React from 'react';
import PropTypes from 'prop-types';
import { withRouter, Link } from 'react-router-dom';
import AddEditExperiment from './AddEditExperiment';
import WhitelistExperiment from './WhitelistExperiment';
import {
  stringifyNull,
  getAudienceRules,
  ruleOperatorMap,
  isComplexRule,
} from './experimentHelpers';
import { openModal, notifyError, notifySuccess } from 'razorx/components/Modal';
import { splitzFetch } from 'razorx/helpers/fetch';
import AsyncButton from 'razorx/components/ui/AsyncButton';
import ExperimentsModal from 'razorx/views/Experiments/Modal';
import { statusPill } from 'razorx/helpers/data';
import Timeline from 'razorx/components/ui/Timeline';
import { TextAreaField } from 'razorx/components/ui/Field';
import { isRzpApprover } from 'razorx/user';
import Comment from 'razorx/components/ui/Comment';
import { EXPERIMENT_DELETE } from './constants';
import { formatDate } from 'razorx/helpers/utils';

// eslint-disable-next-line react/no-unsafe
@withRouter
export default class ExperimentDetails extends React.Component {
  optionalRemarks = null;

  state = {
    isFetchingExperiment: false,
    isFetchingProject: false,
    isFetchingExclusionGroup: false,
    data: null, // experiment
    workflowStatus: null,
    project: null,
    exclusionGroup: null,
    stateLogs: null,
    submittedComment: null,
    commentHistory: [],
    lastEvaluatedAt: null,
  };

  componentDidMount() {
    this.fetch(this.props.experimentId);
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
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
      workflowStatus: null,
    });

    splitzFetch({
      url: 'experiment.v1.ExperimentAPI/Get',
      data: {
        id: experimentId,
        expands: ['workflow', 'state_change_log'],
      },
    })
      .then((res) => {
        this.setState({
          workflowStatus: res.workflows[0]?.workflow?.status || [],
          submittedComment: res.workflows[0]?.workflow?.states?.L1_Approval?.actions[0]?.comment,
          isFetchingExperiment: false,
          data: res.experiment,
          stateLogs: res.state_change_logs,
          commentHistory: res.workflows.map((item) => {
            return item?.workflow?.states?.L1_Approval?.actions[0];
          }),
          lastEvaluatedAt: res?.experiment?.metadata?.last_evaluated_at,
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

  submitExperiment = () => {
    const { data } = this.state;
    const { experimentId, collection } = this.props;

    splitzFetch({
      url: 'experiment.v1.ExperimentAPI/SubmitForApproval',
      data: {
        id: data?.id,
      },
    }).then(() => {
      this.fetch(experimentId);
      collection.fetch();
    });
  };

  approveRejectExperiment = (approvalStatus) => {
    const { data } = this.state;
    const { experimentId, collection } = this.props;

    splitzFetch({
      url: 'experiment.v1.ExperimentAPI/Action',
      data: {
        id: data?.id,
        approval_status: approvalStatus,
        comment: this.optionalRemarks,
      },
    })
      .then(() => {
        this.fetch(experimentId);
        collection.fetch();
        notifySuccess('Success: Please refresh the page if you do not see your changes.');
      })
      .catch((err) => {
        notifyError(err);
      });
  };

  updateExperimentStatus = (experimentStatus) => {
    const { data } = this.state;
    const { experimentId, collection } = this.props;

    splitzFetch({
      url: 'experiment.v1.ExperimentAPI/Action',
      data: {
        id: data?.id,
        status: experimentStatus,
      },
    }).then(() => {
      this.fetch(experimentId);
      collection.fetch();
    });
  };

  terminateExperiment = () => {
    const { data } = this.state;
    const { experimentId, collection } = this.props;

    splitzFetch({
      url: 'experiment.v1.ExperimentAPI/Action',
      data: {
        id: data?.id,
        status: 'terminated',
      },
    }).then(() => {
      this.fetch(experimentId);
      collection.fetch();
    });
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
      whitelistedIds: this.getWhitelistedIds(variant?.id)?.ids,
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
      return whitelist;
    }

    return [];
  };

  deleteExperiment = () => {
    const { data } = this.state;
    const { collection, hideDetails } = this.props;
    splitzFetch({
      url: EXPERIMENT_DELETE,
      data: {
        id: data?.id,
      },
    })
      .then(() => {
        collection.fetch();
        hideDetails();
        notifySuccess('Success: Experiment deleted!');
      })
      .catch((err) => {
        notifyError(err);
      });
  };

  render() {
    const {
      isFetchingExperiment,
      isFetchingProject,
      isFetchingExclusionGroup,
      data,
      workflowStatus,
      project,
      exclusionGroup,
      stateLogs,
      submittedComment,
      commentHistory,
      lastEvaluatedAt,
    } = this.state;
    const { experimentId } = this.props;

    const isFetching = isFetchingExperiment || isFetchingProject || isFetchingExclusionGroup;
    let content;

    const type = {
      ramping: 'Ramp',
      split: 'A/B',
    };

    const evaluationStrategies = {
      default_strategy: 'Default(Audience after Sampling)',
      sampling_on_audience: 'Sampling after Audience',
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
      const isJsonEmpty = !data.audience;
      let audienceRules, isRuleComplex;

      if (isJsonEmpty) {
        audienceRules = undefined;
      } else {
        isRuleComplex = isComplexRule(data.audience);
      }

      if (!isJsonEmpty && !isRuleComplex) {
        audienceRules = getAudienceRules(data.audience);
      }

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
          {lastEvaluatedAt && <div>Last evaluated at {formatDate(lastEvaluatedAt)}</div>}
          <div className="pad-highlight">
            <div className="title">{data.name}</div>
            <div className="description">
              {data.description}
              <Timeline data={data} stateLogs={stateLogs} />
            </div>
          </div>
          <br />
          {statusPill(data.status)}
          {['initiated', 'created'].includes(workflowStatus) ? (
            <span className="pill">Pending</span>
          ) : null}
          <br />
          <br />
          {data.status === 'activated' ? (
            <div>
              <AsyncButton
                type="button"
                className="link info text-info text-bold"
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
          {['created', 'terminated'].includes(data.status) &&
          (['rejected', 'processed'].includes(workflowStatus) || !workflowStatus.length) ? (
            <>
              {submittedComment && <Comment commentHistory={commentHistory} />}
              <br />
              <AsyncButton
                type="button"
                className="link text-bold"
                pendingClass="link danger-faded text-danger text-bold btn-pending"
                onClick={this.submitExperiment}
              >
                Submit for Approval
                <span className="dot-loader">.</span>
              </AsyncButton>
            </>
          ) : null}
          {['initiated', 'created'].includes(workflowStatus) ? (
            <>
              <br />
              <form>
                <TextAreaField
                  fieldClass="approval-form"
                  label="Optional Remarks"
                  name="remarks"
                  onChange={(e) => {
                    this.optionalRemarks = e.target.value;
                  }}
                />
                <br />
                {isRzpApprover() === true ? (
                  <>
                    <AsyncButton
                      type="button"
                      className="link text-bold text-success"
                      onClick={() => {
                        this.approveRejectExperiment('approved');
                      }}
                    >
                      Approve
                    </AsyncButton>
                    <br />
                  </>
                ) : null}
                <AsyncButton
                  type="button"
                  className="link text-bold text-danger"
                  onClick={() => {
                    this.approveRejectExperiment('rejected');
                  }}
                >
                  Reject
                </AsyncButton>
              </form>
            </>
          ) : null}
          {data.status === 'activated' ? (
            <>
              {submittedComment && <Comment commentHistory={commentHistory} />}
              <br />
              <AsyncButton
                type="button"
                className="link danger text-danger text-bold"
                pendingClass="link danger-faded text-danger text-bold btn-pending"
                confirm={`Do you want to Terminate Experiment id "${data.id}"?`}
                onClick={() => {
                  this.updateExperimentStatus('terminated');
                }}
              >
                Terminate Experiment
                <span className="dot-loader">.</span>
              </AsyncButton>
            </>
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
              <Link className="link" to={`/splitz/projects/${project.id}`}>
                View Project
              </Link>
            </div>
            <div className="flex-row-item">
              <div className="label">Evaluation Order</div>
              <span className="square-pills label-semi-muted">
                {data.evaluation_strategy
                  ? evaluationStrategies[data.evaluation_strategy]
                  : evaluationStrategies.default_strategy}
              </span>
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
                  <div className="sub-description column">
                    <div>
                      <b>ID: </b> {variant.id}
                    </div>
                  </div>
                  <div>
                    <span className="square-pills label-semi-muted">
                      <b>Name:</b> {variant.name}
                    </span>
                  </div>
                  <div>
                    <span className="square-pills label-semi-muted">
                      <b>Weight Percentage:</b> {variant.weight}
                    </span>
                  </div>
                  {variant.variables ? (
                    <div className="sub-segment entity-label">
                      {variant.variables.map((variable, i) => (
                        <div key={i}>
                          <span className="label">{variable.key}: &nbsp;</span>
                          <span className="sub-segment-group">{variable.value}</span>
                        </div>
                      ))}
                    </div>
                  ) : null}
                  <br />
                  {whitelistedIds?.segment_id && (
                    <div className="entity-label">
                      <span className="label">Whitelisted Segment: </span>
                      <a
                        className="link"
                        onClick={() =>
                          this.props?.history?.push(
                            `/splitz/segments/${whitelistedIds?.segment_id}`,
                          )
                        }
                      >
                        <span className="sub-segment-group">{whitelistedIds?.segment_id}</span>
                      </a>
                    </div>
                  )}
                  {whitelistedIds?.ids?.length ? (
                    <div className="sub-segment entity-label">
                      <br />
                      <span className="label">Whitelisted IDs: &nbsp;</span>
                      {whitelistedIds.ids.map((id, i) => (
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

          {!isJsonEmpty && isRuleComplex && (
            <>
              <br />
              <br />
              <div className="pad-highlight">
                <div className="title" style={{ position: 'inherit', fontSize: '18px' }}>
                  Audience Rules
                </div>
                <pre>{JSON.stringify(JSON.parse(data.audience), undefined, 2)}</pre>
              </div>
            </>
          )}

          {!isJsonEmpty &&
          !isRuleComplex &&
          audienceRules &&
          audienceRules.rules.length &&
          audienceRules.rules[0].operator ? (
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
                        <b>&nbsp;{ruleOperatorMap[rule.operator]}&nbsp;</b>
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
                        <div>{stringifyNull(rule.value)}</div>
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
                  <Link className="link" to={`/splitz/groups/${exclusionGroup.id}`}>
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
          {data?.mentions?.length > 0 && (
            <>
              <br />
              <div>
                <div className="title section-heading">Slack Mention(s)</div>
                <p>{data?.mentions.join(', ')}</p>
              </div>
            </>
          )}
          <br />
          <br />
          <div>
            <AsyncButton
              type="button"
              className="btn btn-delete"
              confirm="Are you sure you want to delete the experiment?"
              onClick={this.deleteExperiment}
            >
              Delete Experiment
            </AsyncButton>
          </div>
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
