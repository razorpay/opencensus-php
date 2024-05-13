import React from 'react';
import moment from 'moment';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { Link, Navigate } from 'react-router-dom';

import Spinner from 'common/ui/Spinner';
import DataTable from 'common/ui/Table/DataTable';
import { idItem } from 'common/ui/item/id';
import DocsLink from 'merchant/components/DocsLink';
import { fetchTerminalProviders } from 'merchant/reducers/navigator/details';
import { trackOptimizerEvents } from 'merchant/views/Navigator/track';

import ProviderNewView from './ProviderNewView';
import { ProviderShimmer } from './ProviderShimmer';
import { getValue, getRuleStatus, removeMid, uniqueArray, findProviderName } from './util';

@connect(
  (state) => {
    const { navigator, session } = state;
    const { rules, loading, default_rule, terminalProviders, providers_loading } = navigator;
    return {
      rules: rules ?? [],
      isLoading: loading,
      default_rule,
      isProvidersLoading: providers_loading,
      terminalProviders,
      user: session?.user,
    };
  },
  {
    fetchTerminalProviders,
  },
)
export default class RuleList extends React.Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };
  state = {
    redirect: null,
    isCollapsed: true,
  };
  componentDidMount() {
    const { location, fetchTerminalProviders } = this.props;
    if (location?.search?.includes('?redirect')) {
      const id = location.search.replace('?redirect=', '');
      setTimeout(() => {
        this.setState({ redirect: `/optimizer/rules/${id}` });
      }, 1000);
    }
    fetchTerminalProviders();
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

  collapse = () => {
    const { isCollapsed } = this.state;
    this.setState({ isCollapsed: !isCollapsed });
  };

  trackEventOnAddProvider = () => {
    trackOptimizerEvents({
      objectName: 'add provider',
      actionName: 'click',
    });
  };

  render() {
    const { isCollapsed, redirect } = this.state;
    const { isLoading, terminalProviders, isProvidersLoading, default_rule, rules } = this.props;
    const docsLinkProps = {
      url: 'https://razorpay.com/docs/payments/optimizer/',
      title: <span>Documentation</span>,
    };

    if (redirect) return <Navigate to={redirect} replace />;

    return (
      <div className="routing-container">
        <div className="panel gateway-list" style={{ borderLeft: 0, borderRight: 0 }}>
          <div className="panel-header">
            <h2 className="payment-gateway-title" style={{ marginTop: '20px' }}>
              <span className="provider-title">
                Payment Provider
                <span className="total-providers">
                  {isProvidersLoading ? (
                    <div className="dotted-animation" />
                  ) : (
                    terminalProviders?.length
                  )}
                </span>
              </span>
              {terminalProviders?.length > 4 && (
                <span className="collapse-action-span" onClick={this.collapse}>
                  {isCollapsed ? 'View All' : 'Hide All'}
                  <img
                    src="https://cdn.razorpay.com/static/assets/rewards/rewards_list_up_vector.svg"
                    className={`arrow-img${isCollapsed ? ' arrow-img-rotate' : ''}`}
                  />
                </span>
              )}
              <div className="pull-right header-right-container">
                <DocsLink {...docsLinkProps} />
                <Link to="/optimizer/add-provider">
                  <button
                    className="pull-right no-border create-rule-act"
                    type="button"
                    onClick={this.trackEventOnAddProvider}
                  >
                    <i className="i i-plus" /> Add provider
                  </button>
                </Link>
              </div>
            </h2>
          </div>
          <div
            class="panel-body"
            style={{
              borderBottom: '1px solid rgba(0, 0, 0, 0.1)',
              padding: '10px 30px 15px !important',
            }}
          >
            <div
              className={`row active-providers-list ${
                isCollapsed ? 'collapsed-providers-view' : 'expand-providers-view'
              }`}
            >
              {isProvidersLoading ? (
                <ProviderShimmer />
              ) : (
                terminalProviders
                  .slice(0, isCollapsed ? 4 : terminalProviders.length)
                  ?.map((provider, index) => <ProviderNewView provider={provider} key={index} />)
              )}
            </div>
          </div>

          <div className="panel-header">
            <h2 className="payment-gateway-title default-rule-title">
              <span className="provider-title">Default Rule</span>
              {default_rule?.id && (
                <Link to={`/optimizer/rules/${default_rule.id}`} class="pull-right">
                  <button type="button" className="pull-right no-border create-rule-act">
                    View Default Rule
                  </button>
                </Link>
              )}
            </h2>
          </div>
          <div class="panel-body pt0" style={{ paddingTop: '0 !important', marginBottom: '10px' }}>
            <div class="row">
              <div className="col-xs-12">
                <p style={{ marginTop: '-13px', paddingLeft: '20px !important' }}>
                  All transactions which do not fall under the custom rules will be routed via{' '}
                  <Link to={`/optimizer/rules/${default_rule?.id}`}>
                    <span className="rule-status-label default-rule-status-label status-label label label-info">
                      {uniqueArray(
                        (default_rule?.rules ?? [])
                          .filter((i) => i?.expression?.operands?.[0]?.operands)
                          .map((i) => {
                            let id = i?.expression?.operands?.[0]?.operands?.[1]?.value;
                            const id_arr = id?.split('_');
                            if (id_arr?.length > 0) {
                              id = id_arr[id_arr?.length - 1];
                            }
                            return findProviderName(terminalProviders, id);
                          }),
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
              <span className="provider-title">All Custom Rules</span>
              <Link to="/optimizer/create-rule" className="pull-right">
                <button className="btn btn-primary">
                  <i className="i i-plus" /> Add New Rule
                </button>
              </Link>
            </h2>
          </div>
          <div class="panel-body pt0" style={{ paddingLeft: '25px !important' }}>
            <div class="row">
              <div className="col-xs-12">
                {isLoading ? (
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
                      items={rules}
                      empty_placeholder={
                        <div class="rule-list-empty-placeholder">
                          <p className="text-center no-rule">No custom rule set!</p>
                          <p className="text-center start-now">Create a new custom rule now.</p>
                          <div class="text-center">
                            <Link to="/optimizer/create-rule">
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
                          value: (v) => rules?.indexOf(v) + 1,
                        },
                        {
                          title: 'Rule Name',
                          value: (item) => {
                            const url = 'optimizer/rules';
                            return (
                              <div class="rule-table-overflow">
                                <Link to={`/${url}/${item?.id}`}>
                                  {idItem(removeMid(item?.name))}
                                </Link>
                              </div>
                            );
                          },
                        },
                        {
                          title: 'Condition On',
                          value: (v) => {
                            const OP =
                              v?.precondition?.type === 'logical'
                                ? v?.precondition?.operands
                                : [v?.precondition];
                            return (
                              <div class="rule-table-overflow">
                                {uniqueArray(
                                  OP.map(
                                    (o) =>
                                      getValue('parameter', o?.operands?.[0]?.value)?.name ||
                                      o?.operands?.[0]?.value.split('.')[1],
                                  ),
                                ).join(', ')}
                              </div>
                            );
                          },
                        },
                        {
                          title: 'Provider Used',
                          value: (val) => (
                            <div className="rule-table-overflow">
                              {uniqueArray(
                                (val?.rules ?? []).map((i) => {
                                  let id = i?.expression?.operands?.[0]?.operands?.[1]?.value;
                                  const id_arr = id?.split('_');
                                  if (id_arr?.length > 0) {
                                    id = id_arr[id_arr?.length - 1];
                                  }
                                  return findProviderName(terminalProviders, id);
                                }),
                              ).join(', ')}
                            </div>
                          ),
                        },
                        {
                          title: 'Created At',
                          value: (v) => moment(v?.created_at).format('DD/MM/YYYY'),
                        },
                        {
                          title: 'Status',
                          value: (v) => {
                            const status = getRuleStatus(v);
                            return (
                              <span
                                class={`rule-detail-mode rule-status-label status-label label label-${
                                  status === 'live' ? 'success' : 'info'
                                }`}
                              >
                                {status === 'live' ? 'Live' : 'Draft'}
                              </span>
                            );
                          },
                        },
                      ]}
                    />
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      </div>
    );
  }
}
