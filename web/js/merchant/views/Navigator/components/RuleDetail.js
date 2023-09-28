import Spinner from 'common/ui/Spinner';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import React, { Component, Fragment } from 'react';
import { connect } from 'react-redux';
import { Link, Navigate } from 'react-router-dom';
import { withRouter } from 'common/deprecated/withRouter';
import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import Popover, { PopoverBody } from 'common/ui/Popover';
import moment from 'moment';
import {
  fetchRule,
  reorderRules,
  changeRuleMode,
  fetchRules,
  deleteRule,
} from 'merchant/reducers/navigator/details';
import Precondition from './Precondition';

import ProviderRules from './ProviderRules';
import { ReorderRules } from './ReorderRule';
import PropTypes from 'prop-types';
import { deepClone } from 'common/utils/rzp-utils';
import {
  TOTAL_RULE_LIMIT,
  getRuleStatus,
  removeMid,
  getRuleScore,
  total_live_rules,
  parameters,
  createMappedProviders,
} from './util';
import DeactivateRule from './DeactivateRule';

@connect(
  (state) => {
    return {
      ...state.payment,
      default_refund_speed: state.config.config.default_refund_speed,
      config: state.config.config,
      rules: state.navigator.rules,
      rule_detail_loading: state.navigator.rule_detail_loading,
      rules_loaded: state.navigator.rules_loaded,
      default_rule: state.navigator.default_rule,
      rule: state.navigator.rule,
      terminalProviders: state.navigator.terminalProviders,
    };
  },
  {
    ...ModalActions,
    ...NotificationsActions,
    reorderRules,
    fetchRule,
    fetchRules,
    deleteRule,
    changeRuleMode,
  },
)
class RuleDetail extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };
  componentDidMount() {
    this.getRule(this.props.id);
    if (!this.props.rules_loaded && !this.props.rules.length) {
      this.props.fetchRules();
    }
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.getRule(nextProps.id);
    }
  }

  getRule = (id) => {
    this.props.fetchRule(id).then((rule) => {
      const rules = {};
      rule.rules.forEach((r) => {
        const provider_priority = r.additional_attribute[0].value;
        if (!rules[provider_priority]) {
          rules[provider_priority] = [];
        }
        rules[provider_priority].push(r);
      });
    });
  };

  state = { redirect: null };

  deactivateRule = (cb) => {
    this.props.openModal({
      size: 'large',
      component: <DeactivateRule rules={total_live_rules(this.props.rules)} onSuccess={cb} />,
    });
  };

  deleteRule = () => {
    this.context
      .confirm({
        header: 'Are you sure want to delete this rule?',
        affirmativeLabel: 'Yes, Delete',
        affirmativePendingLabel: 'Deleting...',
        abortLabel: 'Cancel',
        action: () => {
          this.props.deleteRule(this.props.rule.id).then(() => {
            this.setState({ redirect: '/optimizer/rules' });
          });
        },
      })
      .catch(() => {});
  };

  render() {
    if (this.state.redirect) {
      return <Navigate to={this.state.redirect} replace />; // nosemgrep : https://semgrep.dev/s/razorpay:rzp-react-router-redirect
    }
    const { terminalProviders } = this.props;
    const MAPPED_PROVIDERS = createMappedProviders(terminalProviders);

    const rules = deepClone(this.props.rules);
    rules.forEach((r) => {
      if (r.id == (this.props.rule && this.props.rule.id)) {
        r.current = true;
      }
    });
    const status = getRuleStatus(this.props.rule);
    return (
      <div class="content-wrapper content-sm txn-details navigator-rule-detail">
        {this.props.rule_detail_loading ? (
          <div class="page-spinner-container">
            <Spinner />
          </div>
        ) : (
          <div class="panel panel-default SliderPanel">
            <div class="panel-heading">
              <div className="row">
                <div
                  className="col-xs-7"
                  style={{
                    whiteSpace: 'nowrap',
                    overflow: 'hidden',
                    textOverflow: 'ellipsis',
                  }}
                >
                  <b>{removeMid(this.props.rule.name)}</b>
                </div>
                <div className="col-xs-5">
                  {getRuleStatus(this.props.rule) == 'test' && !this.props.rule.is_default ? (
                    <button
                      className="btn btn-icon"
                      onClick={this.deleteRule}
                      style={{
                        right: '165px',
                        position: 'absolute',
                        background: 'rgba(11, 112, 231, 0.05)',
                        top: '20px',
                        padding: '7px 10px',
                        border: '1px solid #2B83EA',
                      }}
                    >
                      {' '}
                      <i style={{ color: '#2B83EA' }} className="i i-delete-outline" />
                    </button>
                  ) : null}

                  <Link to={`/optimizer/update-rule/${this.props.rule.id}`}>
                    <button className="btn btn-primary edit-rule-btn">
                      {' '}
                      <i style={{ marginRight: '6px' }} className="i i-pencil-edit" />
                      Edit Rule
                    </button>
                  </Link>
                </div>
              </div>
            </div>

            <div class="SliderPanel__Body">
              <div class="panel-body">
                {!this.props.rule.is_default ? (
                  <div class="list-group details-row-container">
                    <EntityDetailRow
                      label="Rule Priority"
                      value={() => (
                        <div>
                          {getRuleScore(this.props.rule)}
                          {status === 'live' ? (
                            <button
                              onClick={() => {
                                this.props.openModal({
                                  size: 'large',
                                  component: (
                                    <ReorderRules
                                      rules={rules}
                                      onSuccess={(rules) => {
                                        this.props.reorderRules(rules).then(() => {
                                          this.props.showNotification({
                                            type: 'success',
                                            message: 'Rule priority has been updated successully',
                                            closeTimeout: 5000,
                                          });
                                          this.props.closeModal();
                                        });
                                      }}
                                      onClose={this.props.closeModal}
                                    />
                                  ),
                                });
                              }}
                              className="no-border create-rule-act"
                            >
                              {' '}
                              Change Priority
                            </button>
                          ) : null}
                        </div>
                      )}
                    />
                  </div>
                ) : null}
                <div class="list-group details-row-container">
                  <EntityDetailRow
                    label="Status"
                    value={() => (
                      <Fragment>
                        <span
                          class={`rule-detail-mode status-label rule-status-label label label-${
                            status == 'live' ? 'success' : 'info'
                          }`}
                        >
                          {`${status == 'live' ? 'Live' : 'Draft'}`}
                        </span>
                        {!this.props.rule.is_default ? (
                          status === 'live' ? (
                            <button
                              onClick={() => {
                                this.context
                                  .confirm({
                                    header: 'Are you sure want to deactivate this rule?',
                                    message: () => (
                                      <p style={{ marginBottom: '15px' }}>
                                        Rule will be saved in draft after <br /> deactivation .
                                      </p>
                                    ),
                                    affirmativeLabel: 'Yes, Deactivate',
                                    affirmativePendingLabel: 'Deactivating...',
                                    abortLabel: 'Cancel',
                                    action: () => {
                                      this.props
                                        .changeRuleMode(this.props.rule.id, 'test')
                                        .then(() => this.props.fetchRules())
                                        .then(() => {
                                          this.props.showNotification({
                                            type: 'success',
                                            message: 'Rule has been deactivated successully',
                                            closeTimeout: 5000,
                                          });
                                        });
                                    },
                                  })
                                  .catch(() => {});
                              }}
                              className="no-border create-rule-act publish-rule-detail-btn"
                            >
                              {' '}
                              Deactivate Rule
                              <span>
                                <i className="i i-info-outline add-priority-info-c" />
                                <Popover theme="dark" align="bottom">
                                  <PopoverBody>
                                    <p>Rule will be saved in draft after deactivation</p>
                                  </PopoverBody>
                                </Popover>
                              </span>
                            </button>
                          ) : (
                            <button
                              onClick={() => {
                                this.context
                                  .confirm({
                                    header: 'Are you sure want to activate this rule?',
                                    message: () => (
                                      <p style={{ marginBottom: '15px' }}>
                                        Rule will be saved in live mode after publishing .
                                      </p>
                                    ),
                                    affirmativeLabel: 'Yes, Publish',
                                    affirmativePendingLabel: 'Publishing...',
                                    abortLabel: 'Cancel',
                                    action: () => {
                                      let promise;
                                      if (total_live_rules(rules).length >= TOTAL_RULE_LIMIT) {
                                        promise = new Promise((resolve) => {
                                          this.deactivateRule((deactivated_rule) => {
                                            resolve(
                                              this.props
                                                .changeRuleMode(deactivated_rule.id, 'test')
                                                .then(() => {
                                                  rules.forEach((r, i) => {
                                                    if (r.id == deactivated_rule.id) {
                                                      rules.splice(i, 1);
                                                    }
                                                  });
                                                  return this.props.fetchRules();
                                                }),
                                            );
                                          });
                                        });
                                      } else {
                                        promise = Promise.resolve();
                                      }
                                      promise.then(() => {
                                        const rule = { ...this.props.rule, current: true };
                                        this.props.openModal({
                                          size: 'large',
                                          component: (
                                            <ReorderRules
                                              rules={[
                                                ...rules.filter((r) => r.id !== rule.id),
                                                rule,
                                              ]}
                                              onSuccess={(rules) => {
                                                this.props
                                                  .reorderRules(rules, this.props.rule)
                                                  .then(() => {
                                                    this.props.showNotification({
                                                      type: 'success',
                                                      message:
                                                        'Rule has been activated successully',
                                                      closeTimeout: 5000,
                                                    });
                                                    this.props.closeModal();
                                                  });
                                              }}
                                              onClose={this.props.closeModal}
                                            />
                                          ),
                                        });
                                      });
                                    },
                                  })
                                  .catch(() => {});
                              }}
                              className="btn btn-outline no-border create-rule-act publish-rule-detail-btn"
                            >
                              {' '}
                              Publish Rule
                            </button>
                          )
                        ) : null}
                      </Fragment>
                    )}
                  />
                </div>
                {/* {!this.props.rule.is_default ? (
                  <div class="list-group details-row-container">
                    <EntityDetailRow
                      label="Rule Name"
                      value={() => removeMid(this.props.rule.name)}
                    />
                  </div>
                ) : null} */}
                {!this.props.rule.is_default ? (
                  <div class="list-group details-row-container">
                    <EntityDetailRow
                      label="Rule Description"
                      value={() => this.props.rule.description}
                    />
                  </div>
                ) : null}
                <div class="list-group details-row-container precondition-form-pair">
                  <EntityDetailRow
                    label="Rule Condition"
                    value={() =>
                      !this.props.rule.is_default ? (
                        <Precondition
                          parameters={parameters}
                          readonly={true}
                          precondition={this.props.rule.precondition}
                        />
                      ) : (
                        <div style={{ padding: '5px' }}>
                          Apply to transaction when it doesn’t satisfy any rule
                        </div>
                      )
                    }
                  />
                </div>{' '}
                <div class="list-group details-row-container precondition-form-pair provider-rules-form-pair">
                  <EntityDetailRow
                    label="Target Provider"
                    value={() => (
                      <div style={{ paddingLeft: '10px' }}>
                        <ProviderRules
                          rules={(() => {
                            const rules = {};
                            this.props.rule.rules.forEach((r) => {
                              const provider_priority = r.additional_attribute[0].value;
                              if (!rules[provider_priority]) {
                                rules[provider_priority] = [];
                              }
                              rules[provider_priority].push(r);
                            });
                            return rules;
                          })()}
                          readonly={true}
                          providers={MAPPED_PROVIDERS}
                        />
                      </div>
                    )}
                  />
                </div>
                {!this.props.rule.is_default ? (
                  <div class="list-group details-row-container">
                    <EntityDetailRow
                      label="Created By"
                      value={() => (
                        <div>
                          {this.props.rule.created_by} on{' '}
                          {moment(this.props.rule.created_at).format('DD/MM/YYYY')}
                        </div>
                      )}
                    />
                  </div>
                ) : null}
                <div class="list-group details-row-container">
                  <EntityDetailRow
                    label="Last Edited By"
                    value={() => (
                      <div>
                        {this.props.rule.created_by} on{' '}
                        {moment(this.props.rule.updated_at).format('DD/MM/YYYY')}
                      </div>
                    )}
                  />
                </div>
              </div>
            </div>
          </div>
        )}
      </div>
    );
  }

  enableInstantRefunds = () => {
    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Instant Refund',
      eventAction: 'Enable Now',
      eventLabel: `Refund detail page | Enable Now`,
    });
  };
}

export default withRouter(RuleDetail);
