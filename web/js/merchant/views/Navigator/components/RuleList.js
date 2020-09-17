import { connect } from 'react-redux';
import { Route, Switch, NavLink, Link, Redirect } from 'react-router-dom';
import { Fragment } from 'react';
import CreateRule, { rule } from './CreateRule';
import { EntityTable } from 'merchant/components/EntityTable';
import DataTable from 'common/ui/Table/DataTable';
import { getValue, getRuleStatus, removeMid, getRuleScore, uniqueArray } from './util';
import { PROVIDERS } from './ProviderRow';
import ListFilter from 'merchant/components/ListFilter';
import Field from 'common/new-ui/Input';
import { titleCase } from 'common/utils/rzp-utils';
import Provider from './Provider';
import { idItem } from 'common/ui/item/id';
import { fetchRules, fetchRuleProviders } from 'merchant/reducers/navigator/details';
import Popover, { PopoverBody } from 'common/ui/Popover';
import Spinner from 'common/ui/Spinner';

@connect(
  (state) => {
    return {
      rules: state.navigator.rules,
      isLoading: state.navigator.loading,
      default_rule: state.navigator.default_rule,
      providers: state.navigator.providers,
    };
  },
  {
    fetchRules: fetchRules,
    fetchRuleProviders: fetchRuleProviders,
  },
)
export default class RuleList extends React.Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };
  state = {
    redirect: null,
  };
  componentDidMount() {
    if (this.props.location.search.includes('?redirect')) {
      const id = this.props.location.search.replace('?redirect=', '');
      setTimeout(() => {
        this.setState({ redirect: '/navigator/rules' + id });
      }, 1000);
    }
  }

  addGateway = () => {
    this.context.confirm({
      header: 'Add Provider',
      message: (
        <div>
          You can add the payment provider by sending an email to Razorpay with the payment provider
          name and the method you want to enable. <a className="nav-">Contact Support</a>
        </div>
      ),
      affirmativeLabel: `Got It!`,
      abortLabel: 'Close',
    });
  };

  render() {
    if (this.state.redirect) {
      return <Redirect to={this.state.redirect} />;
    }
    return (
      <Fragment>
        <div>
          <div class="panel gateway-list" style={{ borderLeft: 0, borderRight: 0 }}>
            <div class="panel-header">
              <h2 class="payment-gateway-title" style={{ marginTop: '20px' }}>
                Payment Provider
                <button onClick={this.addGateway} className="pull-right no-border create-rule-act">
                  {' '}
                  Add New Gateway
                </button>
              </h2>
            </div>
            <div
              class="panel-body"
              style={{
                borderBottom: '1px solid rgba(0, 0, 0, 0.1)',
                padding: '10px 30px 15px !important',
              }}
            >
              <div class="row" style={{ marginBottom: '10px' }}>
                {this.props.providers.map((g, i) => (
                  <div class="col-xs-3" key={i}>
                    <Provider provider={g} />
                  </div>
                ))}
              </div>
            </div>

            <div class="panel-header">
              <h2 class="payment-gateway-title" style={{ marginTop: '20px', marginBottom: '10px' }}>
                Default Rule
                <Link to={`/navigator/rules/${this.props.default_rule.id}`} class="pull-right">
                  <button className="pull-right no-border create-rule-act">
                    {' '}
                    View Default Rule
                  </button>
                </Link>
              </h2>
            </div>
            <div
              class="panel-body pt0"
              style={{ paddingTop: '0 !important', marginBottom: '10px' }}
            >
              <div class="row">
                <div className="col-xs-12">
                  <p style={{ marginTop: '-13px', paddingLeft: '20px !important' }}>
                    All transactions which do not fall under the custom rules will be routed via{' '}
                    <Link to={`/navigator/rules/${this.props.default_rule.id}`}>
                      <span className="rule-status-label default-rule-status-label status-label label label-info">
                        {uniqueArray(
                          this.props.default_rule.rules.map((i) =>
                            titleCase(i.expression.operands[0].operands[1].value),
                          ),
                        ).join(', ')}
                      </span>
                    </Link>
                  </p>
                </div>
              </div>
            </div>
          </div>

          <div class="panel gateway-list">
            <div class="panel-header" style={{ paddingRight: '15px' }}>
              <h2
                class="payment-gateway-title all-custom-rule-title"
                style={{ marginTop: '20px !important' }}
              >
                All Custom Rules
                <Link to={'/navigator/create-rule'} class="pull-right">
                  <button className="btn btn-primary">
                    {' '}
                    <i className="i i-plus" />
                    Add New Rule
                  </button>
                </Link>
              </h2>
            </div>
            <div class="panel-body pt0" style={{ paddingLeft: '25px !important' }}>
              <div class="row">
                <div className="col-xs-12">
                  {this.props.isLoading ? (
                    <div class="page-spinner-container">
                      <Spinner />
                    </div>
                  ) : (
                    <div
                      class="content-wrapper"
                      style={{ paddingTop: 0, paddingLeft: '0', paddingRight: 0 }}
                    >
                      <DataTable
                        title="Rules"
                        empty_placeholder={
                          <div class="rule-list-empty-placeholder">
                            <p className="text-center no-rule">No custom rule set!</p>
                            <p className="text-center start-now">
                              Create a new custom rule now. <a class="nav-link">Learn More</a>
                            </p>
                            <div class="text-center">
                              <Link to={'/navigator/create-rule'}>
                                <button style={{ marginTop: '15px' }} className="btn btn-primary">
                                  <i className="i i-plus" /> Add New Rule
                                </button>
                              </Link>
                            </div>
                          </div>
                        }
                        columns={[
                          {
                            title: 'Priority',
                            value: (v) => this.props.rules.indexOf(v) + 1,
                          },
                          {
                            title: 'Rule Name',
                            value: (item) => {
                              const url = 'navigator/rules';
                              return (
                                <div class="rule-table-overflow">
                                  <Link to={`/${url}/${item.id}`}>
                                    {idItem(removeMid(item.name))}
                                  </Link>
                                </div>
                              );
                            },
                          },
                          {
                            title: 'Condition On',
                            value: (v) => {
                              const OP =
                                v.precondition.type == 'logical'
                                  ? v.precondition.operands
                                  : [v.precondition];
                              return (
                                <div class="rule-table-overflow">
                                  {uniqueArray(
                                    OP.map((o) => getValue('parameter', o.operands[0].value).name),
                                  ).join(', ')}
                                </div>
                              );
                            },
                          },
                          {
                            title: 'Provider Used',
                            value: (v) => (
                              <div class="rule-table-overflow">
                                {uniqueArray(
                                  v.rules.map((i) => i.expression.operands[0].operands[1].value),
                                ).join(', ')}
                              </div>
                            ),
                          },
                          {
                            title: 'Created At',
                            value: (v) => moment(v.created_at).format('DD/MM/YYYY'),
                          },
                          {
                            title: 'Status',
                            value: (v) => {
                              const status = getRuleStatus(v);
                              return (
                                <span
                                  class={`rule-detail-mode rule-status-label status-label label label-${
                                    status == 'live' ? 'success' : 'info'
                                  }`}
                                >
                                  {status == 'live' ? 'Live' : 'Draft'}
                                </span>
                              );
                            },
                          },
                        ]}
                        items={this.props.rules}
                      />
                    </div>
                  )}
                </div>
              </div>
            </div>
          </div>
        </div>
      </Fragment>
    );
  }
}
