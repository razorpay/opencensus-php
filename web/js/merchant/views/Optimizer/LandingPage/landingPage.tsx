import React, { useState, useEffect } from 'react';
import { Box, Spinner } from '@razorpay/blade/components';
import moment from 'moment';
import { connect } from 'react-redux';
import { Link, Navigate } from 'react-router-dom';
import { compose, bindActionCreators } from 'redux';

import DataTable from 'common/ui/Table/DataTable';
import { idItem } from 'common/ui/item/id';
import DocsLink from 'merchant/components/DocsLink';
import { fetchTerminalProviders } from 'merchant/reducers/navigator/details';
import { trackOptimizerEvents } from 'merchant/views/Optimizer/track';
import {
  getValue,
  getRuleStatus,
  removeMid,
  uniqueArray,
  findProviderName,
} from 'merchant/views/Optimizer/utils';

import { ProviderView } from './ProviderView';
import { ProviderShimmer } from './ProviderShimmer';

const LandingPage = (props): JSX.Element => {
  const [redirectURL, setRedirectURL] = useState('');
  const [isCollapsed, setIsCollapsed] = useState(true);

  const { isLoading, terminalProviders, isProvidersLoading, default_rule, rules } = props;

  useEffect(() => {
    const { location, fetchTerminalProviders } = props;
    if (location?.search?.includes('?redirect')) {
      const id = location.search.replace('?redirect=', '');
      setTimeout(() => {
        setRedirectURL(`/optimizer/rules/${id}`);
      }, 1000);
    }
    if (terminalProviders?.length <= 0) {
      fetchTerminalProviders();
    }
  }, []);

  const collapse = () => {
    setIsCollapsed(!isCollapsed);
  };

  const trackEventOnAddProvider = () => {
    trackOptimizerEvents({
      objectName: 'add provider',
      actionName: 'click',
    });
  };

  const docsLinkProps = {
    // eslint-disable-next-line i18n-rules/no-hardcoded-i18n-types
    url: 'https://razorpay.com/docs/payments/optimizer/',
    title: 'Documentation',
    onClick: () => {
      trackOptimizerEvents({
        objectName: 'documentation',
        actionName: 'click',
      });
    },
  };

  if (redirectURL) return <Navigate to={redirectURL} replace />;

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
              <span className="collapse-action-span" onClick={collapse}>
                {isCollapsed ? 'View All' : 'Hide All'}
                {/* eslint-disable-next-line i18n-rules/no-region-specific-image */}
                <img
                  // eslint-disable-next-line i18n-rules/no-hardcoded-i18n-types
                  src="https://cdn.razorpay.com/static/assets/rewards/rewards_list_up_vector.svg"
                  className={`arrow-img${isCollapsed ? ' arrow-img-rotate' : ''}`}
                  alt="arrow-img"
                />
              </span>
            )}
            <div className="pull-right header-right-container">
              <DocsLink {...docsLinkProps} />
              <Link to="/optimizer/add-provider">
                <button
                  className="pull-right no-border create-rule-act"
                  type="button"
                  onClick={trackEventOnAddProvider}
                >
                  <i className="i i-plus" /> Add provider
                </button>
              </Link>
            </div>
          </h2>
        </div>
        <div className="panel-body">
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
                ?.map((provider, index) => <ProviderView provider={provider} key={index} />)
            )}
          </div>
        </div>

        <div className="panel-header">
          <h2 className="payment-gateway-title default-rule-title">
            <span className="provider-title">Default Rule</span>
            {default_rule?.id && (
              <Link to={`/optimizer/rules/${default_rule.id}`} className="pull-right">
                <button type="button" className="pull-right no-border create-rule-act">
                  View Default Rule
                </button>
              </Link>
            )}
          </h2>
        </div>
        <div className="panel-body pt0">
          <div className="row">
            <div className="col-xs-12">
              <p>
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

      <div className="panel gateway-list">
        <div className="panel-header rule-header">
          <h2 className="payment-gateway-title all-custom-rule-title">
            <span className="provider-title">All Custom Rules</span>
            <Link to="/optimizer/create-rule" className="pull-right">
              <button className="btn btn-primary">
                <i className="i i-plus" /> Add New Rule
              </button>
            </Link>
          </h2>
        </div>
        <div className="panel-body pt0">
          <div className="row">
            <div className="col-xs-12">
              {isLoading ? (
                <Box height="350px">
                  <Spinner
                    color="primary"
                    accessibilityLabel="loadingRules"
                    size="xlarge"
                    marginTop="9%"
                    marginLeft="50%"
                  />
                </Box>
              ) : (
                <div className="content-wrapper">
                  <DataTable
                    title="Rules"
                    items={rules}
                    empty_placeholder={
                      <div className="rule-list-empty-placeholder">
                        <p className="text-center no-rule">No custom rule set!</p>
                        <p className="text-center start-now">Create a new custom rule now.</p>
                        <div className="text-center">
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
                            <div className="rule-table-overflow">
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
                            <div className="rule-table-overflow">
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
                              className={`rule-detail-mode rule-status-label status-label label label-${
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
};

const mapStateToProps = (state) => {
  const { navigator } = state;
  const { rules, loading, default_rule, terminalProviders, providers_loading } = navigator;
  return {
    rules: rules ?? [],
    isLoading: loading,
    default_rule,
    isProvidersLoading: providers_loading,
    terminalProviders,
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      fetchTerminalProviders,
    },
    dispatch,
  );
};

export default compose<any>(connect(mapStateToProps, mapDispatchToProps))(LandingPage);
