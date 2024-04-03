import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import moment from 'moment';
import { Spinner } from '@razorpay/blade/components';
import { showNotification } from 'merchant_common/reducers/notifications';
import { OrderAnalyticsReportsWrapper } from 'merchant/views/MagicCheckout/OrderAnalytics/Reports/styled';
import { getOrderAnalyticsReports } from 'merchant/views/MagicCheckout/OrderAnalytics/api';
import { downloadFromUrl } from 'merchant/views/MagicCheckout/common/helpers';
import { reportsCards } from 'merchant/views/MagicCheckout/OrderAnalytics/Reports/constants';
import { Category } from 'merchant/views/MagicCheckout/OrderAnalytics/Reports/types';
import ReportsCard from 'merchant/views/MagicCheckout/OrderAnalytics/Reports/components/ReportsCard';

const initialReportsStart = moment().subtract(2, 'days').startOf('day').unix();
const initialReportsEnd = moment().startOf('day').unix();

const Reports = (props) => {
  const { reportsTimeRange, setReportsTimeRange, showNotification, dashboardView } = props;
  const [loading, setLoading] = useState<Category | null>(null);

  const getDownloadLink = async (category: Category) => {
    setLoading(category);
    try {
      const orderAnalyticsReports = await getOrderAnalyticsReports({
        category,
        from: reportsTimeRange.start,
        to: reportsTimeRange.end,
        dashboardView,
      });
      const fileLink = orderAnalyticsReports?.data?.file_url;
      downloadFromUrl(showNotification, fileLink);
    } catch (err: any) {
      showNotification({
        type: 'error',
        message: err?.errors?.[0] || 'Something went wrong while downloading file',
      });
    }
    setLoading(null);
  };

  useEffect(() => {
    setReportsTimeRange({
      start: initialReportsStart,
      end: initialReportsEnd,
    });
  }, []);

  return (
    <OrderAnalyticsReportsWrapper>
      {
        <div className="order-analytics-reports">
          {loading ? (
            <div className="spinner-container">
              Generating {loading} Reports
              <Spinner accessibilityLabel="magic-order-analytics-reports-spinner" />
            </div>
          ) : (
            reportsCards.map((reportsCard) => (
              <ReportsCard
                key={reportsCard.category}
                getDownloadLink={getDownloadLink}
                category={reportsCard.category}
                heading={reportsCard.heading}
                desc={reportsCard.desc}
              />
            ))
          )}
        </div>
      }
    </OrderAnalyticsReportsWrapper>
  );
};

const mapStateToProps = (state) => ({ dashboardView: state.magicCheckout.dashboard_view });

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      showNotification,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(Reports);
