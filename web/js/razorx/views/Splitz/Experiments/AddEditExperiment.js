import React from 'react';
import PropTypes from 'prop-types';
import { withRouter } from 'react-router-dom';
import { PowerSelect } from 'react-power-select';
import {
  stringifyNull,
  getAudienceRules,
  isComplexRule,
  createAudienceRules,
  isEmptyRules,
  ruleOperatorMap,
  RULE_TYPE,
} from './experimentHelpers';
import { ModalContent } from 'common/new-ui/Modal';
import { closeModal, notifySuccess, notifyError } from 'razorx/components/Modal';
import Form from 'razorx/components/ui/Form';
import Field, {
  TextAreaField,
  SelectField,
  SearchableSelectField,
} from 'razorx/components/ui/Field';
import { splitzFetch } from 'razorx/helpers/fetch';

@withRouter
export default class AddEditExperiment extends React.Component {
  state = this.initState();

  mentions = null;

  initState() {
    let variants = [
      {
        name: '',
        weight: '',
        variables: [
          {
            key: '',
            value: '',
          },
        ],
      },
    ];
    let ruleCondition = 'and';
    let rules = [
      {
        key: '',
        operator: '',
        value: '',
      },
    ];
    let selectedType = 'ramping';

    let ruleType = RULE_TYPE.simpleRule;
    let complexRules = '';
    if (this.props.isEdit) {
      // handle segments case
      variants = this.props.data.variants;
      ruleType =
        this.props.data.audience && isComplexRule(this.props.data.audience)
          ? RULE_TYPE.complexRule
          : RULE_TYPE.simpleRule;
      selectedType = this.props.data.type;
      complexRules = this.props.data.audience;

      if (ruleType === RULE_TYPE.simpleRule) {
        const audienceRules = this.props.data.audience
          ? getAudienceRules(this.props.data.audience)
          : undefined;
        ruleCondition = audienceRules?.ruleCondition || ruleCondition;
        rules = audienceRules?.rules || rules;
      }
    }
    return {
      complexRules,
      isSaving: false,
      isFetchingProjects: true,
      isFetchingSegments: true,
      isFetchingGroups: false,
      isJsonValid: false,
      groups: [],
      projects: [],
      segments: [],
      ruleCondition,
      rules,
      ruleType,
      variants,
      selectedType,
      selectedProject: null,
      selectedGroup: null,
    };
  }

  onSubmit = (form) => {
    const isInvalid = this.isInvalid(form);

    if (!!isInvalid) {
      notifyError(isInvalid);
      return;
    }

    const experimentPayload = {
      experiment: {
        id: this.props.data.id,
        name: form.name,
        description: form.description,
        type: form.type,
        evaluation_strategy: form.evaluationStrategy,
        sampling_percentage: Number(form.samplingPercentage),
        project_id: this.state.selectedProject.id,
        exclusion_group: this.state.selectedGroup
          ? {
              entity_id: this.state.selectedGroup.id,
              value: Number(form.trafficAllocation),
            }
          : undefined,
        audience:
          this.state.ruleType === RULE_TYPE.complexRule
            ? this.state.complexRules
            : isEmptyRules(this.state.rules)
            ? undefined
            : createAudienceRules({
                ruleCondition: this.state.ruleCondition,
                rules: this.state.rules,
              }),
        variants: this.state.variants,
        mentions: this?.mentions?.replace(/ /g, '').split(','),
      },
    };

    let experimentUrl = 'experiment.v1.ExperimentAPI/';
    let successMsg = '';

    if (this.props.isEdit) {
      experimentUrl += 'Update';
      successMsg = `Experiment ${this.props.data.id} is successfully updated`;
    } else {
      experimentUrl += 'Create';
      successMsg = 'Experiment is successfully created';
    }

    this.setState({ isSaving: true });

    // add / update experiment
    splitzFetch({ url: experimentUrl, data: experimentPayload })
      .then((res) => {
        this.setState({ isSaving: false });
        notifySuccess(successMsg);
        closeModal();

        this.props.collection.fetch();

        if (this.props.isEdit) {
          this.props.onEdit();
        } else {
          this.props.history.push(`/splitz/experiments/${res.experiment.id}`);
        }
      })
      .catch((err) => {
        this.setState({ isSaving: false });
        notifyError(err);
      });
  };

  isInvalid(form) {
    if (!this.state.selectedProject || !this.state.selectedProject.id) {
      return 'Please select a project';
    }

    if (form.trafficAllocation && form.trafficAllocation % 5 !== 0) {
      return 'Traffic allocation should be in multiple of 5';
    }

    if (this.state.selectedGroup && this.state.selectedGroup.id && !form.trafficAllocation) {
      return 'Please add traffic allocation';
    }

    if (form.trafficAllocation && (!this.state.selectedGroup || !this.state.selectedGroup.id)) {
      return 'Please select an exclusion group';
    }

    if (!this.state.variants.length || !this.state.variants[0].name.length) {
      return 'Please add a variant';
    }

    if (this.state.ruleType === RULE_TYPE.complexRule && !this.state.isJsonValid) {
      return 'Please add valid JSON';
    }

    let totalWeight = 0;
    for (const variant of this.state.variants) {
      let weight = variant.weight;
      if (weight == undefined || weight === '') {
        return 'Variant weight is a required field';
      }
      if (isNaN(weight)) {
        return 'Variant weight must be an integer';
      }

      weight = parseInt(weight, 10);
      if (weight < 0 || weight > 100) {
        return 'Variant weight value must be between 0 and 100(both included)';
      }
      totalWeight += weight;
    }

    if (totalWeight != 100) {
      return `Total weight all variants must be 100. Current weight: ${totalWeight}`;
    }

    if (this.state.selectedType === 'split' && this.state.variants.length < 2) {
      return 'A/B experiment should have at least 2 variants';
    }

    return false;
  }

  handleSelectProject = ({ option: project }) => {
    if (!project) {
      this.setState({
        selectedProject: null,
        groups: [],
        selectedGroup: null,
      });
      return;
    }

    this.setState({
      selectedProject: project,
      isFetchingGroups: true,
      groups: [],
      selectedGroup: null,
    });

    splitzFetch({
      url: 'exclusion_group.v1.ExclusionGroupAPI/List',
      data: {
        project_id: project.id,
        limit: 100,
        offset: 0,
      },
    })
      .then((groupRes) => {
        this.setState({
          isFetchingGroups: false,
          groups: groupRes.items || [],
        });

        if (this.props.isEdit) {
          if (this.props.data.exclusion_group) {
            const currentGroup = groupRes.items.find(
              (group) => group.id === this.props.data.exclusion_group.entity_id,
            );
            this.handleSelectGroup({
              option: currentGroup,
            });
          }
        }
      })
      .catch(() => {
        this.setState({
          isFetchingGroups: false,
        });
      });
  };

  handleSelectGroup = ({ option }) => {
    this.setState({ selectedGroup: option });
  };

  handleSelectChange = (event) => {
    this.setState({
      ruleType: event.target.value,
    });
  };

  handleComplexRuleChange = (event) => {
    const json = event.target.value;
    let isJsonValid = false;

    try {
      JSON.parse(json);
      isJsonValid = true;
    } catch (error) {
      isJsonValid = false;
    }

    this.setState({
      complexRules: json,
      isJsonValid,
    });
  };

  formatJson = () => {
    const { complexRules } = this.state;

    const formattedJson = JSON.stringify(JSON.parse(complexRules), undefined, 2);
    this.setState({ complexRules: formattedJson });
  };

  componentDidMount() {
    Promise.all([
      splitzFetch({
        url: 'project.v1.ProjectAPI/List',
        data: {
          limit: 1000,
          offset: 0,
        },
      }),
      splitzFetch({
        url: 'segment.v1.SegmentAPI/List',
        data: {
          limit: 1000,
          offset: 0,
        },
      }),
    ])
      .then((allResponses) => {
        const [projectRes, segmentRes] = allResponses;

        this.setState({
          isFetchingProjects: false,
          isFetchingSegments: false,
          projects: projectRes.items,
          segments: segmentRes.items,
        });

        if (this.props.isEdit) {
          const currentProject = projectRes.items.find(
            (project) => project.id === this.props.data.project_id,
          );

          this.handleSelectProject({
            option: currentProject,
          });
        }
      })
      .catch(() => {
        this.setState({
          isFetchingProjects: false,
          isFetchingSegments: false,
        });
      });
  }

  getSelectedSegment = (segmentId) => {
    const { segments } = this.state;
    if (!segments || !segments.length) {
      return {};
    }

    return segments.find((s) => s.id === segmentId);
  };

  storeMentionValue = (e) => {
    this.mentions = e?.target?.value || '';
  };

  render() {
    const { data, isEdit } = this.props;
    const {
      isSaving,
      isFetchingProjects,
      isFetchingGroups,
      isFetchingSegments,
      projects,
      groups,
      selectedType,
      selectedProject,
      selectedGroup,
      ruleCondition,
      rules,
      variants,
      segments,
    } = this.state;
    const isLoading = isSaving || isFetchingProjects || isFetchingGroups || isFetchingSegments;

    const header = isEdit ? `Edit Experiment – ${data.id}` : 'Create Experiment';

    const evaluationStrategyOptions = [
      {
        label: 'Default(Audience after Sampling)',
        value: 'default_strategy',
      },
      {
        label: 'Sampling after Audience',
        value: 'sampling_on_audience',
      },
    ].map((op) => (
      <option key={op.value} value={op.value}>
        {op.label}
      </option>
    ));

    const ruleTypeOptions = [
      {
        label: 'Simple Rule',
        value: RULE_TYPE.simpleRule,
      },
      {
        label: 'Complex Rule',
        value: RULE_TYPE.complexRule,
      },
    ].map((ruleOptions) => (
      <option key={ruleOptions.value} value={ruleOptions.value}>
        {ruleOptions.label}
      </option>
    ));

    return (
      <ModalContent class="modal-features modal-json-edit" header={header}>
        <Form
          onSubmit={this.onSubmit}
          class="full-span full-elements"
          style={{ opacity: isLoading ? 0.5 : 1 }}
        >
          <React.Fragment>
            {isLoading && <div className="spinner center" style={{ zIndex: 99 }} />}
            <Field
              type="text"
              label="Name"
              name="name"
              placeholder="Experiment Name"
              defaultValue={isEdit ? data.name : ''}
              required
            />
            <TextAreaField
              label="Description"
              name="description"
              placeholder="Experiment Description"
              defaultValue={isEdit ? data.description : ''}
              required
            />
            <SelectField
              name="type"
              label="Type"
              defaultValue={isEdit ? data.type : 'ramping'}
              required
              disabled={!!this.props.data.id}
              onChange={(o) => {
                const type = o.target.value;
                this.setState({ selectedType: type });
                if (type === 'split') {
                  this.setState({
                    variants: [
                      {
                        name: '',
                        variables: [
                          {
                            key: '',
                            value: '',
                          },
                        ],
                      },
                      {
                        name: '',
                        variables: [
                          {
                            key: '',
                            value: '',
                          },
                        ],
                      },
                    ],
                  });
                }
              }}
            >
              {[
                {
                  label: 'Ramp',
                  value: 'ramping',
                },
                {
                  label: 'A/B',
                  value: 'split',
                },
              ].map((op, i) => (
                <option key={i} value={op.value}>
                  {op.label}
                </option>
              ))}
            </SelectField>
            <SelectField
              name="evaluationStrategy"
              label="Evaluation Order"
              defaultValue={isEdit ? data.evaluation_strategy : 'default_strategy'}
              required
            >
              {evaluationStrategyOptions}
            </SelectField>
            <Field
              type="number"
              label="Sampling Percentage"
              name="samplingPercentage"
              placeholder="Sampling Percentage"
              defaultValue={isEdit ? data.sampling_percentage : ''}
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
            <SearchableSelectField
              name="exclusion_group"
              optionComponent={({ option }) => (
                <div>
                  {option.name} - {option.id}
                </div>
              )}
              placeholder="Select an exclusion group"
              searchIndices={['id', 'name']}
              label="Exclusion Group"
              trackBy="id"
              options={
                groups.length
                  ? groups
                  : [{ name: 'No exclusion groups found for selected project' }]
              }
              selected={selectedGroup}
              onChange={this.handleSelectGroup}
            />
            {selectedGroup && selectedGroup.id ? (
              <Field
                type="number"
                label="Traffic Allocation"
                name="trafficAllocation"
                placeholder="0-100 (Multiple of 5)"
                defaultValue={isEdit && data.exclusion_group ? data.exclusion_group.value : ''}
              />
            ) : null}
            <br />
            <br />
            <div style={{ border: '1px solid rgba(0,0,0,0.05)', padding: '25px' }}>
              <div className="title" style={{ position: 'inherit', fontWeight: 'bold' }}>
                Audience Rules
              </div>
              <SelectField
                name="ruleType"
                label="Rule Type"
                defaultValue={isEdit ? this.state.ruleType : RULE_TYPE.simpleRule}
                onChange={this.handleSelectChange}
                required
              >
                {ruleTypeOptions}
              </SelectField>
              {this.state.ruleType === RULE_TYPE.simpleRule ? (
                <>
                  <SelectField
                    name="ruleCondition"
                    label="Rule Condition"
                    defaultValue={ruleCondition}
                    value={ruleCondition}
                    onChange={({ target: { value } }) => this.setState({ ruleCondition: value })}
                  >
                    {['and', 'or'].map((op, i) => (
                      <option key={i} value={op}>
                        {op.toUpperCase()}
                      </option>
                    ))}
                  </SelectField>

                  {rules.map((rule, i) => (
                    <div key={i}>
                      <div className="flex-row" style={{ alignItems: 'center' }}>
                        <input
                          type="text"
                          placeholder="Key"
                          value={rule.key}
                          onChange={(e) => {
                            const newRules = [...rules];
                            newRules[i] = {
                              ...newRules[i],
                              key: e.target.value,
                            };
                            this.setState({
                              rules: newRules,
                            });
                          }}
                        />
                        <PowerSelect
                          options={Object.keys(ruleOperatorMap)}
                          optionComponent={(op) => ruleOperatorMap[op.option]}
                          searchEnabled={false}
                          showClear={false}
                          placeholder="operator"
                          selected={ruleOperatorMap[rule.operator]}
                          onChange={({ option }) => {
                            const newRules = [...rules];
                            newRules[i] = {
                              ...newRules[i],
                              operator: option,
                            };
                            this.setState({
                              rules: newRules,
                            });
                          }}
                        />
                        {['belongsTo', 'doesNotBelongTo'].includes(rule.operator) ? (
                          <SearchableSelectField
                            name=""
                            optionComponent={({ option }) => (
                              <div>
                                {option.name} - {option.id}
                              </div>
                            )}
                            placeholder="Select a segment"
                            searchIndices={['id', 'name']}
                            trackBy="id"
                            options={segments || []}
                            selected={this.getSelectedSegment(rule.value)}
                            onChange={(o) => {
                              const newRules = [...rules];
                              newRules[i] = {
                                ...newRules[i],
                                value: o.option.id,
                              };
                              this.setState({
                                rules: newRules,
                              });
                            }}
                            selectStyleProps={{
                              margin: '0',
                              width: '97%',
                            }}
                          />
                        ) : (
                          <input
                            type="text"
                            placeholder="Value"
                            value={stringifyNull(rule.value)}
                            onChange={(e) => {
                              const newRules = [...rules];
                              newRules[i] = {
                                ...newRules[i],
                                value: e.target.value,
                              };
                              this.setState({
                                rules: newRules,
                              });
                            }}
                          />
                        )}

                        <span
                          style={{
                            marginLeft: '2px',
                            fontSize: '20px',
                            width: '25px',
                          }}
                          className="cross"
                          onClick={() => {
                            this.setState({
                              rules: rules.filter((r, index) => index !== i),
                            });
                          }}
                        />
                      </div>
                      {i < rules.length - 1 && (
                        <div className="flex-row" style={{ justifyContent: 'center' }}>
                          <span style={{ margin: '8px' }} className="square-pills label-semi-muted">
                            {ruleCondition.toUpperCase()}
                          </span>
                        </div>
                      )}
                    </div>
                  ))}
                  <br />
                  <div className="flex-row" style={{ justifyContent: 'center' }}>
                    <button
                      type="button"
                      className="btn btn--pill"
                      onClick={() => {
                        this.setState({
                          rules: [
                            ...rules,
                            {
                              operator: '',
                              key: '',
                              value: '',
                            },
                          ],
                        });
                      }}
                    >
                      + Add Rule
                    </button>
                  </div>
                </>
              ) : (
                <>
                  <TextAreaField
                    label="Complex Rules"
                    placeholder="Enter your complex conditions here"
                    value={this.state.complexRules}
                    defaultValue={isEdit ? this.state.complex_rules : ''}
                    onChange={this.handleComplexRuleChange}
                  />
                  <button
                    type="button"
                    onClick={this.formatJson}
                    disabled={!this.state.isJsonValid}
                  >
                    Format JSON
                  </button>
                </>
              )}
            </div>
            <br />
            <br />
            <div style={{ border: '1px solid rgba(0,0,0,0.05)', padding: '25px' }}>
              <div className="title" style={{ position: 'inherit', fontWeight: 'bold' }}>
                Variants*
              </div>
              {variants.map((variant, variantIndex) => {
                let variantLabel = variantIndex + 1;
                if (selectedType === 'split') {
                  variantLabel = variantIndex === 0 ? 'Control' : variantIndex;
                }

                return (
                  <div key={variantIndex}>
                    <div className="flex-row" style={{ alignItems: 'center' }}>
                      <div className="label">{`Variant #${variantLabel}`}</div>
                      <span
                        className="cross cross-right"
                        onClick={() => {
                          this.setState({
                            variants: variants.filter((v, index) => index !== variantIndex),
                          });
                        }}
                      />
                    </div>
                    <div className="flex-row" style={{ alignItems: 'center' }}>
                      <Field
                        type="text"
                        label="Name"
                        name="variantName"
                        placeholder="Variant Name"
                        value={variant.name}
                        onChange={(e) => {
                          const newVariants = [...variants];
                          newVariants[variantIndex].name = e.target.value;
                          this.setState({
                            variants: newVariants,
                          });
                        }}
                      />
                    </div>
                    <div className="flex-row">
                      <Field
                        type="text"
                        label="Weight Percentage"
                        name="variantWeight"
                        placeholder="Variant Weight Percentage"
                        value={variant.weight}
                        onChange={(e) => {
                          const newVariants = [...variants];
                          newVariants[variantIndex].weight = e.target.value;
                          this.setState({
                            variants: newVariants,
                          });
                        }}
                      />
                    </div>
                    {variant.variables.map((variable, variableIndex) => (
                      <div key={variableIndex}>
                        <div className="flex-row" style={{ alignItems: 'center' }}>
                          <input
                            type="text"
                            placeholder="Key"
                            value={variable.key}
                            onChange={(e) => {
                              const newVariants = [...variants];
                              newVariants[variantIndex].variables[variableIndex].key =
                                e.target.value;
                              this.setState({
                                variants: newVariants,
                              });
                            }}
                            style={{ width: '50%' }}
                          />
                          <input
                            type="text"
                            placeholder="Value"
                            value={variable.value}
                            onChange={(e) => {
                              const newVariants = [...variants];
                              newVariants[variantIndex].variables[variableIndex].value =
                                e.target.value;
                              this.setState({
                                variants: newVariants,
                              });
                            }}
                            style={{ width: '50%' }}
                          />
                          <span
                            className="cross"
                            onClick={() => {
                              const newVariants = [...variants];
                              newVariants[variantIndex].variables = newVariants[
                                variantIndex
                              ].variables.filter((v, index) => index !== variableIndex);
                              this.setState({
                                variants: newVariants,
                              });
                            }}
                          />
                        </div>
                      </div>
                    ))}
                    <div className="flex-row">
                      <button
                        type="button"
                        className="btn btn--pill"
                        style={{ color: 'grey', marginTop: '10px' }}
                        onClick={() => {
                          const newVariants = [...variants];
                          newVariants[variantIndex].variables.push({
                            key: '',
                            value: '',
                          });
                          this.setState({
                            variants: newVariants,
                          });
                        }}
                      >
                        + Add Key/Value
                      </button>
                    </div>
                    <br />
                  </div>
                );
              })}
              <br />
              <div className="flex-row" style={{ justifyContent: 'center' }}>
                <button
                  type="button"
                  className="btn btn--pill"
                  onClick={() => {
                    this.setState({
                      variants: [
                        ...variants,
                        {
                          name: '',
                          variables: [
                            {
                              key: '',
                              value: '',
                            },
                          ],
                        },
                      ],
                    });
                  }}
                >
                  + Add Variant
                </button>
              </div>
            </div>
            <br />
            <div className="field-container">
              <h3 className="title subheading">Slack Mention(s)</h3>
              <p className="side-note">*For adding multiple ids, use comma seperated strings </p>
              <Field
                type="text"
                label="Slack Mentions"
                name="mentions"
                placeholder="Group ID or User ID"
                onChange={this.storeMentionValue}
                defaultValue={isEdit ? data?.mentions : ''}
              />
            </div>
            <div style={{ marginTop: 24 }} />
          </React.Fragment>
          <div className="footer">
            <button className="btn btn--primary">
              {isEdit ? 'Update' : 'Create'} Experiment
              <span className="spin-btn" />
            </button>
          </div>
        </Form>
      </ModalContent>
    );
  }
}

AddEditExperiment.defaultProps = {
  data: {},
  onEdit: () => {},
  isEdit: false,
};

AddEditExperiment.propTypes = {
  collection: PropTypes.object.isRequired,
  history: PropTypes.object,
  data: PropTypes.object,
  onEdit: PropTypes.func,
  isEdit: PropTypes.bool,
};
