import React, { Component } from 'react';
import { connect } from 'react-redux';
import { Routes, Navigate, Route } from 'react-router-dom';

import ShowWhen, { RouteGuard } from 'merchant/components/ShowWhen';

import { fetchCommissionBalances } from 'merchant/reducers/commission';

import Amount from 'common/ui/Amount';
import { isPresent } from 'common/utils/rzp-utils';
import { showNotification } from 'merchant_common/reducers/notifications';

import EarningsTransactionalList from 'merchant/views/PartnerDashboard/Earnings/Transactional/List';
import EarningsDailyList from 'merchant/views/PartnerDashboard/Earnings/Daily/List';
import CommissionInvoicesList from 'merchant/views/PartnerDashboard/Earnings/Invoices/List';
import ProductWrapper from 'common/ui/ProductWrapper';
import DocsLink from 'merchant/components/DocsLink';
import { Box } from 'merchant_common/views/Reports/components';

class EarningsContainer extends Component {
  state = {
    commissionBalance: null,
    tabsData: [
      { url: '/partners/earnings/daily', title: 'Daily Earnings' },
      {
        url: '/partners/earnings/transactional',
        title: 'Transactional Details',
        hidden: !!this.props.user.isPartner('reseller'),
      },
      {
        url: '/partners/earnings/invoices',
        title: 'Invoices',
        hidden: !this.props.user.isCommissionInvoicesEnabled,
      },
    ],
  };

  componentDidMount() {
    this.getCommissionBalance();
  }

  getCommissionBalance = () => {
    fetchCommissionBalances().then((res) => {
      const data = res.data;
      if (data.items.length > 0) {
        const commissionItem = data.items.find((item) => item.type === 'commission');
        isPresent(commissionItem) &&
          this.setState({
            commissionBalance: commissionItem.balance,
          });
      }
    });
  };

  render() {
    const { commissionBalance } = this.state;
    const { user } = this.props;
    const currency = user.merchant.currency;

    return (
      <div className="earnings-page">
        <ProductWrapper
          tabsData={this.state.tabsData}
          extra={
            <>
              <ShowWhen
                additionalCondition={(user) =>
                  user.isShowCommissionBalanceEnabled &&
                  user.isOrgAllowedFunctionality('current_balance') &&
                  (commissionBalance == 0 || commissionBalance)
                }
              >
                <div className="partner-dashboard-header-action">
                  <span className="settlement-balance-amount">
                    Commission Balance: <Amount value={commissionBalance} currency={currency} />
                  </span>
                </div>
              </ShowWhen>

              <Box display="inline">
                <DocsLink title="Documentation" isTab />
              </Box>
            </>
          }
        >
          <content>
            <Routes>
              <Route path="*" element={<Navigate to="/partners/earnings/daily" replace />} />
              <Route
                path="transactional/*"
                element={
                  <RouteGuard additionalCondition={(user) => !user.isPartner('reseller')}>
                    <EarningsTransactionalList />
                  </RouteGuard>
                }
              />
              <Route
                path="invoices/*"
                element={
                  <RouteGuard additionalCondition={(user) => user.isCommissionInvoicesEnabled}>
                    <CommissionInvoicesList />
                  </RouteGuard>
                }
              />

              <Route
                path="daily/*"
                element={
                  <RouteGuard>
                    <EarningsDailyList />
                  </RouteGuard>
                }
              />
            </Routes>
          </content>
        </ProductWrapper>
      </div>
    );
  }
}

export default connect(
  (state) => ({
    user: state.session.user,
  }),
  { showNotification },
)(EarningsContainer);
