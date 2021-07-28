import { connect } from 'react-redux';
import Input, { Description, Label } from 'common/new-ui/Input';
import { deepClone } from 'common/utils/rzp-utils';
import { merchantFetch } from 'merchant/utils/ajax';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import Precondition from './Precondition';
import ProviderRules from './ProviderRules';
import { withRouter, Link } from 'react-router-dom';
import DeactivateRule from './DeactivateRule';
import { Redirect } from 'react-router-dom';
import { Fragment } from 'react';
import { showNotification } from 'merchant_common/reducers/notifications';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { rupeesToPaise } from 'common/utils/rzp-utils';
import Spinner from 'common/ui/Spinner';
import { get_unique, SMART_ROUTER, parameters, createMappedProviders } from './util';
import PreconditionPopover from './PreconditionPopover';
import { CSSTransition } from 'react-transition-group';

import {
  getRules,
  changeRuleMode,
  getRule,
  fetchRules,
  reorderRules,
  updateRule,
  deleteRule,
  createRule,
} from 'merchant/reducers/navigator/details';
import { ReorderRules } from './ReorderRule';
import {
  isExpressionValid,
  mapRulesArrayToObject,
  mapRulesObjectToArray,
  appendMid,
  setRuleMode,
  TOTAL_RULE_LIMIT,
  removeMid,
  total_live_rules,
  DEFAULT_RULE,
  getRuleStatus,
  getRuleScore,
} from './util';
import { PreconditionModel } from '../models/PreconditionModel';
import FullPageCover from './FullPageCover';
import FullPageCoverHeader from './FullPageCoverHeader';

@withRouter
@connect(
  (state) => {
    return {
      ...state,
      rules: state.navigator.rules,
      rule: state.navigator.rule,
      loading: state.navigator.create_rule_loading,
      // isLoading: true,
      providers: state.navigator.providers,
      terminalProviders: state.navigator.terminalProviders,
      user: state.session.user,
    };
  },
  {
    openModal,
    showNotification,
    updateRule,
    fetchRules,
    closeModal,
    deleteRule,
    reorderRules: reorderRules,
    createRule: createRule,
    changeRuleMode: changeRuleMode,
  },
)
export default class CreateRule extends React.Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };
  componentDidMount() {
    document.addEventListener('keydown', this.escFunction);
    if (this.props.match.params.id) {
      const id = this.props.match.params.id;
      this.setState({ loading: true });
      getRule(id)
        .then((rule) => {
          this.setState({
            rule: rule,
            loading: false,
            update: true,
            steps: {
              1: {
                edit: false,
                show: true,
              },
              2: {
                edit: false,
                show: true,
              },
              3: {
                edit: false,
                show: true,
              },
              4: {
                edit: true,
                show: true,
              },
            },
          });
        })
        .catch((e) => {
          this.setState({ loading: false });
          this.props.showNotification({
            type: 'error',
            message: e.errors[0],
          });
        });
    }
  }

  updateStep(index, data) {
    const steps = this.state.steps;
    Object.keys(steps).forEach((k) => {
      steps[k].edit = false;
    });
    steps[index] = { ...steps[index], ...data };
    // steps[index] = { ...steps[index], ...data };
    this.setState({ steps });
  }

  componentWillUnmount() {
    document.removeEventListener('keydown', this.escFunction);
  }

  escFunction = (event) => {
    if (event.keyCode === 27) {
      this.setState({ redirect: '/optimizer/rules' });
    }
  };

  state = {
    is_default: false,
    loading: false,
    update: false,
    redirect: null,
    rule: {
      name: '',
      description: '',
      precondition: new PreconditionModel(),
      rules: [],
    },
    steps: {
      1: {
        edit: true,
        show: true,
      },
      2: {
        edit: false,
        show: false,
      },
      3: {
        edit: false,
        show: false,
      },
      4: {
        edit: false,
        show: false,
      },
    },
  };

  deactivateRule = () => {
    return new Promise((res, rej) => {
      this.props.openModal({
        size: 'large',
        component: (
          <DeactivateRule
            rules={total_live_rules(this.props.rules)}
            onSuccess={(e) => {
              // this.props.closeModal();
              res(e);
            }}
          />
        ),
      });
    });
  };

  get isPreconditionValid() {
    let is_valid = true;
    const precondition = this.state.rule.precondition;
    if (precondition) {
      if (precondition.type == 'logical') {
        precondition.operands.forEach((e) => {
          if (!isExpressionValid(e)) {
            is_valid = false;
          }
        });
      } else {
        if (!isExpressionValid(precondition)) {
          is_valid = false;
        }
      }
    }
    return is_valid;
  }

  openCheckout = () => {
    let user = {
      name: 'adi',
      email: 'aditya.dewaskar@razorpay.com',
      contact_mobile: 9999999999,
    };
    let amountInPaise = rupeesToPaise(1);

    let options = {
      amount: amountInPaise,
      key: 'rzp_test_vNg6XJagghVNFK',
      prefill: {
        name: user.name,
        email: user.email,
        contact: user.contact_mobile,
      },
      notes: {
        dashboard: true,
      },
      handler: function (transaction = {}) {},
    };

    return new Promise((resolve, reject) => {
      try {
        const rzp = new window.Razorpay(options);
        rzp.open();
        resolve();
      } catch (e) {
        reject(`An error occured - ${e.message}`);
      }
    }).catch((error) => {
      this.setState({
        status: {
          type: 'error',
          message: error,
        },
      });
    });
  };

  get isProviderRulesValid() {
    let is_valid = true;
    const rules = mapRulesArrayToObject(this.state.rule.rules);
    if (this.state.rule.rules.length == 0) {
      is_valid = false;
    }
    Object.keys(rules).forEach((k) => {
      let total_load = 0;
      rules[k].forEach((r) => {
        let load = Number(r.additional_attribute[1].value);
        total_load += load;
        if (!(isExpressionValid(r.expression.operands[0]) && load > 0)) {
          is_valid = false;
        }
      });
      if (total_load !== 100) {
        is_valid = false;
      }
    });
    return is_valid;
  }

  get isRuleValid() {
    return (
      this.state.rule.name &&
      (this.state.rule.is_default ? true : this.state.rule.name !== appendMid(DEFAULT_RULE)) &&
      this.state.rule.rules.length &&
      (this.state.rule.is_default ? true : this.isPreconditionValid) &&
      this.isProviderRulesValid
    );
  }

  addNewRow = (provider_priority) => {
    const rule = this.state.rule;
    const rules = mapRulesArrayToObject(this.state.rule.rules);
    const RULES = [];
    let load = null;
    let total_load = 0;
    if (!rules[provider_priority]) {
      rules[provider_priority] = [];
    }
    rules[provider_priority].forEach((r) => {
      const c_load = Number(r.additional_attribute[1].value);
      if (typeof c_load === 'number') {
        total_load += c_load;
      }
    });
    load = 100 - total_load;
    if (load > 0) {
      rules[provider_priority].push({
        name: 'xx_xx_xx',
        expression: {
          type: 'logical',
          value: '&&',
          operands: [
            {
              type: 'comparator',
              value: '==',
              operands: [
                {
                  type: 'variable',
                  value: '$provider.id',
                  operands: null,
                },
                {
                  value: '',
                },
              ],
            },
            {
              type: 'comparator',
              value: 'in',
              operands: [
                {
                  type: 'string',
                  value: 'live',
                  operands: null,
                },
                {
                  type: 'variable',
                  value: '$payment.rule_mode',
                  operands: null,
                },
              ],
            },
          ],
        },
        additional_attribute: [
          {
            name: 'provider_priority',
            value: `${provider_priority}`,
          },
          {
            name: 'load',
            value: `${load}`,
          },
        ],
        skiponfailure: false,
      });
      Object.keys(rules).forEach((key) => {
        RULES.push(...rules[key]);
      });
      rule.rules = RULES;
      this.setState({ rule });
    }
  };

  createRule = (data, mode) => {
    // data.rules = setRuleMode(data.rules, mode);
    return this.props.createRule(data);
  };

  reorderRules = (rules, rule) => {
    let body = {
      order: {
        ordered_names: rules.map((r) => r.name),
      },
    };
    if (rule) {
      body.rule_group_id = rule.id;
    }
    return this.props.reorderRules(body);
  };

  goNext = (i, cb) => {
    let steps = { ...this.state.steps };
    steps[i] = {
      edit: false,
      show: true,
    };
    steps[i + 1] = {
      show: true,
      edit: true,
    };
    this.setState({ steps }, cb);
  };

  reqBody = (mode) => {
    const rule = deepClone(this.state.rule);
    rule.rules.forEach((r) => {
      let random = get_unique();
      // check this for later and replace this with uuid
      r.name = `${random}_${rule.name}`;
      // r.score = this.props.rules.length + 1;
    });
    const req = {
      name: rule.name,
      precondition: rule.precondition,
      rules: setRuleMode(rule.rules, mode),
      description: '',
      // outcome_type: '',
      strategy: 'default',
      // mandatory_attributes: [],
      additional_attributes: [
        {
          name: 'rule_mode',
          type: 'string',
          values: [mode],
        },
      ],
    };
    return req;
  };

  cleanRule = (rule) => {
    rule.rules.forEach((r) => {
      let random = get_unique();
      r.name = `${random}_${rule.name}`;
      r.expression.operands.forEach((o, index) => {
        if (o.operands[0].value === '$merchant.id') {
          r.expression.operands.splice(index, 1);
        }
      });
    });
    if (rule.is_default) {
      delete rule.precondition;
    }
    rule.rules = setRuleMode(rule.rules, rule.additional_attributes[0].values[0]);
    delete rule.outcome_type;
    delete rule.mandatory_attributes;
    return rule;
  };

  addPriority = () => {
    const pp = Object.keys(mapRulesArrayToObject(this.state.rule.rules)).length + 1;
    const r = this.addNewRow(pp);
  };

  render() {
    if (this.state.redirect) {
      return <Redirect to={this.state.redirect} />;
    }
    let PARAMETERS = deepClone(parameters);
    const rules = deepClone(this.props.rules);

    const { user, providers, terminalProviders } = this.props;
    let MAPPED_PROVIDERS = createMappedProviders(user.isAddProviderEnabled, providers, terminalProviders);

    let is_netbanking = false;
    let is_smart_router = false;
    if (this.state.rule.precondition) {
      if (this.state.rule.precondition.type === 'logical') {
        this.state.rule.precondition.operands.forEach((o) => {
          if (o.operands[1].value.includes('netbanking')) {
            is_netbanking = true;
          }
        });
      } else {
        if (this.state.rule.precondition.operands[1].value.includes('netbanking')) {
          is_netbanking = true;
        }
      }
    }

    this.state.rule.rules.forEach((rule) => {
      rule.expression.operands.forEach((o) => {
        if (o.operands[1].value == SMART_ROUTER) {
          is_smart_router = true;
        }
      });
    });
    if (is_netbanking) {
      MAPPED_PROVIDERS = MAPPED_PROVIDERS.map((p) => {
        if (p.id == SMART_ROUTER) {
          p.disabled = true;
        }
        p.disabled_message =
          'Smart Router cannot be added as a provider for conditions with Net Banking currently.';
        return p;
      });
    }
    if (is_smart_router) {
      PARAMETERS.forEach((p) => {
        if (p.name == 'Payment Method') {
          p.values.forEach((v) => {
            if (v.value === 'netbanking') {
              v.disabled = true;
              v.disabled_message =
                'Net Banking cannot be added as a condition with Smart Router as a provider currently.';
            }
          });
        }
      });
    }

    return (
      <FullPageCover>
        <FullPageCoverHeader>
          <div className="container" style={{ hright: '56px' }}>
            <div className="panel" style={{ marginTop: '3px' }}>
              <div className="panel-body">
                <div className="row">
                  <div className="col-xs-4">
                    <h3>
                      {this.state.update ? 'Edit' : 'Create'} Rule{' '}
                      {/* {!this.state.update ? (
                        <a
                          class={`highlight know-more`}
                          target="_blank"
                          style={{ marginLeft: '10px', borderColor: '#EBEFF0', fontSize: '13px' }}
                        >
                          Know more
                          <i class="i i-external-link" style={{ marginLeft: '5px' }} />
                        </a>
                      ) : null} */}
                    </h3>
                  </div>
                  <div className="col-xs-5" />
                  <div className="col-xs-3">
                    <Link to={'/optimizer/rules'}>
                      <span
                        style={{
                          fontSize: '20px',
                          cursor: 'pointer',
                          color: 'rgba(22, 47, 86, 0.54)',
                          fontSize: '17px',
                          fontWeight: 600,
                        }}
                        class="pull-right"
                      >
                        Close <i className="i i-close" />
                      </span>
                    </Link>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </FullPageCoverHeader>
        <div className="container">
          <div class="navigator--create-rule">
            {this.state.loading ? (
              <div class="page-spinner-container">
                <Spinner />
              </div>
            ) : (
              <Fragment>
                {this.state.steps[1].show || this.state.update ? (
                  !this.state.rule.is_default ? (
                    <CSSTransition in={true} appear={true} timeout={800} classNames="slide-up">
                      <div
                        class={`panel gateway-list rule-detail ${
                          this.state.steps[1].edit ? 'active' : ''
                        }`}
                      >
                        <div class="panel-header">
                          <h2 class="payment-gateway-title">
                            Rule Details
                            {!this.state.steps[1].edit ? (
                              <button
                                onClick={() => {
                                  const edit = this.state.steps[1].edit;
                                  this.updateStep(1, {
                                    edit: !edit,
                                  });
                                }}
                                className="pull-right no-border create-rule-act"
                              >
                                {' '}
                                <i className="i i-pencil-edit" /> Edit Rule Details
                              </button>
                            ) : (
                              <span class="pull-right step-text" style={{ fontSize: '16px' }}>
                                {' '}
                                Step 1 Out Of 4
                              </span>
                            )}
                          </h2>
                          {this.state.steps[1].edit ? (
                            <p class="desc">Add a name and description for your custom rule.</p>
                          ) : null}
                        </div>
                        <div class="panel-body">
                          <div class="row">
                            <div className="col-xs-12">
                              <div className="row">
                                <div className="col-xs-2">
                                  <label for="name" style={{ textAlign: 'left' }}>
                                    Rule Name
                                  </label>
                                </div>
                                <div className="col-xs-6">
                                  {!this.state.steps[1].edit ? (
                                    <div class="stepper-readonly">
                                      {removeMid(this.state.rule.name)}
                                    </div>
                                  ) : (
                                    <Input
                                      id="name"
                                      class="Input--vLeft"
                                      name="name"
                                      value={removeMid(this.state.rule.name)}
                                      placeholder="Rule Name"
                                      onChange={(e) => {
                                        const rule = this.state.rule;
                                        rule.name = appendMid(e.target.value);
                                        this.setState({
                                          rule,
                                        });
                                      }}
                                    />
                                  )}
                                </div>
                              </div>
                            </div>
                            <div className="col-xs-12">
                              <div
                                className="row"
                                style={{ marginTop: '10px', marginBottom: '15px' }}
                              >
                                <div className="col-xs-2">
                                  <label for="description" style={{ textAlign: 'left' }}>
                                    Rule Description
                                  </label>
                                </div>
                                <div className="col-xs-6">
                                  {!this.state.steps[1].edit ? (
                                    <div class="stepper-readonly">
                                      {this.state.rule.description}
                                    </div>
                                  ) : (
                                    <textarea
                                      id="description"
                                      class="Input--vLeft form-control"
                                      value={this.state.rule.description}
                                      name="description"
                                      placeholder="Rule Description"
                                      onChange={(e) => {
                                        const rule = this.state.rule;
                                        rule.description = e.target.value;

                                        this.setState({
                                          rule,
                                        });
                                      }}
                                    />
                                  )}
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                        {this.state.steps[1].edit ? (
                          <div className="panel-footer">
                            <button
                              disabled={
                                !(
                                  removeMid(this.state.rule.name) &&
                                  this.state.rule.name !== appendMid(DEFAULT_RULE)
                                )
                              }
                              onClick={() => {
                                this.goNext(1, () => {
                                  if (this.state.rule.is_default) {
                                    this.goNext(2);
                                  }
                                });
                              }}
                              className="btn btn-primary pull-right"
                            >
                              Next <i className="i i-arrow-forward" />
                            </button>
                            <div className="clearfix" />
                          </div>
                        ) : null}
                      </div>
                    </CSSTransition>
                  ) : null
                ) : null}

                {this.state.steps[2].show || this.state.update ? (
                  <CSSTransition in={true} appear={true} timeout={800} classNames="slide-up">
                    <div class={`panel gateway-list ${this.state.steps[2].edit ? 'active' : ''}`}>
                      <div class="panel-header">
                        <h2 class="payment-gateway-title">
                          Rule Conditions
                          {!this.state.rule.is_default ? (
                            !this.state.steps[2].edit ? (
                              <button
                                onClick={() => {
                                  const edit = this.state.steps[2].edit;
                                  this.updateStep(2, {
                                    edit: !edit,
                                  });
                                }}
                                className=" pull-right no-border create-rule-act"
                              >
                                {' '}
                                <i className="i i-pencil-edit" /> Edit Rule Conditions
                              </button>
                            ) : (
                              <span class="pull-right step-text" style={{ fontSize: '16px' }}>
                                {' '}
                                Step 2 Out Of 4
                              </span>
                            )
                          ) : null}
                        </h2>
                        {this.state.steps[2].edit ? (
                          <p class="desc">
                            {this.state.rule.is_default ? (
                              `Default rule will be used as a rule to route transactions that do not satisfy any other rules.`
                            ) : (
                              <span>
                                Add conditions to identify payments.{' '}
                                {/* <a class="nav-link">Learn More</a>{' '} */}
                              </span>
                            )}
                          </p>
                        ) : this.state.rule.is_default ? (
                          <p class="desc">
                            Default rule will be used as a rule to route transactions that{' '}
                            <b>do not satisfy any other rules.</b>
                          </p>
                        ) : null}
                      </div>
                      {!this.state.rule.is_default ? (
                        <CSSTransition in={true} appear={true} timeout={800} classNames="slide-up">
                          <div class="panel-body" style={{ paddingTop: '15px !important' }}>
                            <Precondition
                              parameters={PARAMETERS}
                              readonly={!this.state.steps[2].edit}
                              precondition={this.state.rule.precondition}
                              update={(precondition) => {
                                const rule = this.state.rule;
                                rule.precondition = precondition;
                                this.setState({ rule: rule });
                              }}
                              parent={'create-rule'}
                            />
                          </div>
                        </CSSTransition>
                      ) : null}

                      {this.state.steps[2].edit ? (
                        <div className="panel-footer">
                          <button
                            onClick={() => {
                              this.goNext(2);
                              if (this.state.rule.rules.length == 0 && !this.state.update) {
                                this.addPriority();
                              }
                            }}
                            disabled={!this.isPreconditionValid}
                            className="btn btn-primary pull-right"
                          >
                            Next <i className="i i-arrow-forward" />
                          </button>
                          <div className="clearfix" />
                        </div>
                      ) : null}
                    </div>
                  </CSSTransition>
                ) : null}

                {this.state.steps[3].show || this.state.update ? (
                  <CSSTransition in={true} appear={true} timeout={800} classNames="slide-up">
                    <div class={`panel gateway-list ${this.state.steps[3].edit ? 'active' : ''}`}>
                      <div class="panel-header">
                        <h2 class="payment-gateway-title">
                          Target Payment Provider
                          {!this.state.steps[3].edit ? (
                            <button
                              onClick={() => {
                                const edit = this.state.steps[3].edit;
                                this.updateStep(3, {
                                  edit: !edit,
                                });
                              }}
                              className="pull-right no-border create-rule-act"
                            >
                              {' '}
                              <i className="i i-pencil-edit" /> Edit Target Provider
                            </button>
                          ) : !this.state.rule.is_default ? (
                            <span class="pull-right step-text" style={{ fontSize: '16px' }}>
                              {' '}
                              Step 3 Out Of 4
                            </span>
                          ) : null}
                        </h2>
                        {this.state.steps[3].edit ? (
                          <p class="desc">
                            Add desired payment provider through which the payment has to be routed.{' '}
                            {/* <a class="nav-link">Learn More</a> */}
                          </p>
                        ) : null}
                      </div>
                      <ProviderRules
                        parent={'create-rule'}
                        providers={MAPPED_PROVIDERS}
                        addNewRow={(pp, rp) => this.addNewRow(pp, rp)}
                        update={(e) => {
                          const rule = this.state.rule;
                          let R = {};
                          Object.keys(e).forEach((k, index) => {
                            let ni = index + 1;
                            e[k].forEach((r) => {
                              r.additional_attribute[0].value = `${ni}`;
                              r.score = rule.score;
                            });
                            R[ni] = e[k];
                          });
                          const rules = mapRulesObjectToArray(R);
                          rule.rules = rules;
                          this.setState({ rule });
                        }}
                        rules={mapRulesArrayToObject(this.state.rule.rules)}
                        readonly={!this.state.steps[3].edit}
                      />
                      {this.state.steps[3].edit ? (
                        <div class="panel-body add-exp-cont" style={{ padding: '0px 20px 6px' }}>
                          <div class="row">
                            <div className="col-xs-12">
                              <div class="add-expression" style={{ color: '#2B83EA' }}>
                                <b onClick={this.addPriority} class="pointer">
                                  Add Priority{' '}
                                  {!this.state.rule.is_default ? (
                                    <span>
                                      <i className="i i-info-outline add-priority-info-c" />
                                      <Popover
                                        theme="dark"
                                        align="bottom"
                                        // parentQuerySelector={`.Modal--confirm`}
                                      >
                                        <PopoverBody>
                                          <div>
                                            If transaction fails in a priority then it will fallback
                                            to lower priority.{' '}
                                          </div>
                                        </PopoverBody>
                                      </Popover>
                                    </span>
                                  ) : null}
                                </b>
                              </div>
                            </div>
                          </div>
                        </div>
                      ) : null}
                      {this.state.steps[3].edit ? (
                        <div className="panel-footer">
                          <button
                            onClick={() => {
                              this.goNext(3);
                            }}
                            className="btn btn-primary pull-right"
                            disabled={!this.isProviderRulesValid}
                          >
                            Next <i className="i i-arrow-forward" />
                          </button>
                          <div className="clearfix" />
                        </div>
                      ) : null}
                    </div>
                  </CSSTransition>
                ) : null}
                {this.state.steps[4].show || this.state.update ? (
                  <CSSTransition in={true} appear={true} timeout={800} classNames="slide-up">
                    <div
                      style={{ paddingRight: '28px', paddingLeft: '28px', marginBottom: '80px' }}
                      class={`panel gateway-list ${this.state.steps[4].edit ? 'active' : ''}`}
                    >
                      <div class="panel-header">
                        <h2 class="payment-gateway-title" style={{ marginLeft: 0 }}>
                          Confirm Rule
                        </h2>
                        <p class="desc" style={{ marginLeft: 0 }}>
                          Publish the rule or save as draft to publish later.{' '}
                          {/* <a className="nav-link"> Learn More</a>{' '} */}
                        </p>
                      </div>
                      {this.state.steps[4].edit ? (
                        <div
                          className="panel-footer"
                          style={{
                            borderTop: '1px solid #e3dede',
                            paddingRight: 0,
                            paddingTop: '30px',
                          }}
                        >
                          {!this.state.update ? (
                            <Fragment>
                              <button
                                onClick={() => {
                                  const rule = this.reqBody('test');
                                  let score = rules.length + 1;
                                  rule.rules.forEach((r) => {
                                    r.score = score;
                                  });
                                  let promise;
                                  // if (true) {
                                  if (total_live_rules(rules).length >= TOTAL_RULE_LIMIT) {
                                    promise = this.deactivateRule().then((deactivated_rule) => {
                                      return this.props
                                        .changeRuleMode(deactivated_rule.id, 'test')
                                        .then(() => {
                                          rules.forEach((r, i) => {
                                            if (r.id == deactivated_rule.id) {
                                              rules.splice(i, 1);
                                            }
                                          });
                                          this.props.closeModal();
                                          return this.createRule(rule, 'test');
                                        });
                                    });
                                  } else {
                                    promise = this.createRule(rule, 'test');
                                  }
                                  promise
                                    .then((rule) => {
                                      rule = { ...rule, current: true };
                                      this.props.openModal({
                                        size: 'large',
                                        component: (
                                          <ReorderRules
                                            rules={[...rules.filter((r) => r.id !== rule.id), rule]}
                                            onSuccess={(rules) => {
                                              this.props.reorderRules(rules, rule).then(() => {
                                                this.props.showNotification({
                                                  type: 'success',
                                                  message: 'Rule made live successully',
                                                  closeTimeout: 5000,
                                                });
                                                this.props.closeModal();
                                                this.setState({ redirect: '/optimizer/rules' });
                                              });
                                            }}
                                            onClose={() => {
                                              this.context
                                                .confirm({
                                                  header: 'Are you sure you want to close?',
                                                  message: `Rule will not be published live.`,
                                                  affirmativeLabel: 'Confirm',
                                                  abortLabel: 'Cancel',
                                                })
                                                .then(() => {
                                                  // return Promise.resolve(rule);
                                                  return this.props.deleteRule(rule.id);
                                                })
                                                .then(() => {
                                                  this.props.closeModal();
                                                });
                                            }}
                                          />
                                        ),
                                      });
                                    })
                                    .catch((e) => {
                                      this.props.showNotification({
                                        type: 'error',
                                        message: e.errors[0],
                                      });
                                    });
                                }}
                                disabled={!this.isRuleValid || this.props.loading}
                                className="btn btn-primary pull-right"
                              >
                                {!this.props.loading ? 'Publish Rule' : 'Please Wait..'}
                              </button>
                              <button
                                style={{ marginRight: '10px' }}
                                onClick={() => {
                                  const rule = this.reqBody('test');
                                  let promise;
                                  let score = rules.length + 1;
                                  this.context
                                    .confirm({
                                      header: 'Are you sure want to save rule as draft?',
                                      message: `Rule will be saved in draft at ${score} priority.`,
                                      affirmativeLabel: `Yes, Publish`,
                                      abortLabel: 'Cancel',
                                    })
                                    .then(() => {
                                      rule.rules.forEach((r) => {
                                        r.score = score;
                                      });
                                      return this.createRule(rule, 'test');
                                    })
                                    .then((rule) => {
                                      this.props.showNotification({
                                        type: 'success',
                                        message: 'Rule saved as draft successully',
                                        closeTimeout: 5000,
                                      });
                                      this.setState({ redirect: '/optimizer/rules' });
                                    })
                                    .catch((e) => {
                                      this.props.showNotification({
                                        type: 'error',
                                        message: e.errors[0],
                                      });
                                    });
                                }}
                                disabled={!this.isRuleValid}
                                className="btn btn-outline pull-right no-border create-rule-act"
                              >
                                Save as Draft
                              </button>
                            </Fragment>
                          ) : (
                            <Fragment>
                              <button
                                onClick={() => {
                                  const rule = this.cleanRule(this.state.rule);
                                  rule.rules.forEach((r) => {
                                    let temp = r.name.split('_');
                                    temp.pop();
                                    temp.pop();
                                    temp.push(rule.name);
                                    r.name = temp.join('_');
                                  });
                                  let promise;
                                  this.context
                                    .confirm({
                                      header: 'Are you sure you want to publish edits?',
                                      message: null,
                                      affirmativeLabel: `Yes, Publish`,
                                      abortLabel: 'Cancel',
                                    })
                                    .then(() => {
                                      return this.props.updateRule(rule);
                                    })
                                    .then((R) => {
                                      this.props.showNotification({
                                        type: 'success',
                                        message: 'Rule has been updated successully',
                                        closeTimeout: 5000,
                                      });
                                      let promise;
                                      if (
                                        total_live_rules(rules).length >= TOTAL_RULE_LIMIT &&
                                        getRuleStatus(rule) === 'test'
                                      ) {
                                        promise = this.deactivateRule().then((deactivated_rule) => {
                                          return this.props
                                            .changeRuleMode(deactivated_rule.id, 'test')
                                            .then(() => {
                                              rules.forEach((r, i) => {
                                                if (r.id == deactivated_rule.id) {
                                                  rules.splice(i, 1);
                                                }
                                              });
                                              return this.props.fetchRules();
                                            });
                                        });
                                      } else {
                                        promise = Promise.resolve();
                                      }
                                      return promise;
                                    })
                                    .then(() => {
                                      const index = rules.findIndex((r) => r.id == rule.id);
                                      rule.current = true;
                                      rules[index] = rule;
                                      if (!rule.is_default) {
                                        this.props.openModal({
                                          size: 'large',
                                          component: (
                                            <ReorderRules
                                              rules={rules}
                                              onSuccess={(rules) => {
                                                let args;
                                                if (getRuleStatus(rule) === 'test') {
                                                  args = [rules, rule];
                                                } else {
                                                  args = [rules];
                                                }
                                                this.props
                                                  .reorderRules(...args)
                                                  .then(() => {
                                                    this.props.closeModal();
                                                    this.props.showNotification({
                                                      type: 'success',
                                                      message: 'Rule made live successfully',
                                                      closeTimeout: 5000,
                                                    });
                                                    this.setState({
                                                      redirect: '/optimizer/rules',
                                                    });
                                                  })
                                                  .then(() =>
                                                    getRule(this.state.rule.id).then((R) =>
                                                      this.setState({ rule: R }),
                                                    ),
                                                  )
                                                  .catch((e) => {
                                                    this.props.showNotification({
                                                      type: 'error',
                                                      message: e.errors[0],
                                                    });
                                                  });
                                              }}
                                              onClose={this.props.closeModal}
                                            />
                                          ),
                                        });
                                      } else {
                                        this.props.showNotification({
                                          type: 'success',
                                          message: 'Rule has been updated successully',
                                          closeTimeout: 5000,
                                        });
                                      }
                                    })
                                    .catch((e) => {
                                      this.props.showNotification({
                                        type: 'error',
                                        message: e.errors[0],
                                      });
                                    });
                                }}
                                disabled={!this.isRuleValid}
                                className="btn btn-primary pull-right"
                              >
                                {getRuleStatus(this.state.rule) === 'test'
                                  ? `Publish Rule`
                                  : `Publish Edits`}
                              </button>
                              {getRuleStatus(this.state.rule) === 'live' ? (
                                !this.state.rule.is_default ? (
                                  <button
                                    style={{ marginRight: '10px' }}
                                    onClick={() => {
                                      this.context.confirm({
                                        header: 'Are you sure want to deactivate this rule?',
                                        message: () => (
                                          <p style={{ marginBottom: '15px' }}>
                                            Rule will be saved in draft after deactivation .
                                          </p>
                                        ),
                                        affirmativeLabel: 'Yes, Deactivate',
                                        affirmativePendingLabel: 'Deactivating...',
                                        abortLabel: 'Cancel',
                                        action: () => {
                                          this.props
                                            .changeRuleMode(this.state.rule.id, 'test')
                                            .then(() => {
                                              return getRule(this.state.rule.id);
                                            })
                                            .then(() => this.props.fetchRules())
                                            .then((rules) => {
                                              this.setState({
                                                rule: rules.find((r) => r.id == this.state.rule.id),
                                              });
                                              this.props.showNotification({
                                                type: 'success',
                                                message: 'Rule deactivated successully',
                                                closeTimeout: 5000,
                                              });
                                              this.setState({
                                                redirect: '/optimizer/rules',
                                              });
                                            })
                                            .catch((e) => {
                                              this.props.showNotification({
                                                type: 'error',
                                                message: e.errors[0],
                                              });
                                            });
                                        },
                                      });
                                    }}
                                    disabled={this.props.loading}
                                    className="btn btn-outline pull-right no-border create-rule-act"
                                  >
                                    Deactivate Rule
                                  </button>
                                ) : null
                              ) : (
                                <button
                                  style={{ marginRight: '10px' }}
                                  onClick={() => {
                                    const rule = this.cleanRule(this.state.rule);
                                    rule.rules.forEach((r) => {
                                      let temp = r.name.split('_');
                                      temp.pop();
                                      temp.pop();
                                      temp.push(rule.name);
                                      r.name = temp.join('_');
                                    });
                                    this.context
                                      .confirm({
                                        header: 'Are you sure want to update draft rule?',
                                        // message: `Rule will be saved in draft at ${score} priority.`,
                                        affirmativeLabel: `Yes, Update`,
                                        abortLabel: 'Cancel',
                                      })
                                      .then(() => {
                                        return this.props.updateRule(rule);
                                      })
                                      .then((rule) => {
                                        this.props.showNotification({
                                          type: 'success',
                                          message: 'Draft rule has been updated',
                                          closeTimeout: 5000,
                                        });
                                        this.setState({ redirect: '/optimizer/rules' });
                                      })
                                      .catch((e) => {
                                        this.props.showNotification({
                                          type: 'error',
                                          message: e.errors[0],
                                        });
                                      });
                                  }}
                                  disabled={this.props.loading}
                                  className="btn btn-outline pull-right no-border create-rule-act"
                                >
                                  Save as Draft
                                </button>
                              )}
                            </Fragment>
                          )}
                          <div className="clearfix" />
                        </div>
                      ) : null}
                    </div>
                  </CSSTransition>
                ) : null}
              </Fragment>
            )}
          </div>
        </div>
      </FullPageCover>
    );
  }
}
