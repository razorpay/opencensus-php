import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';
import {
  PaymentPageEntity,
  PaymentHandleListFilterPropTypes,
} from 'merchant/views/PaymentHandle/typings';
import View from '@razorpay/blade-old/src/atoms/View';
import Space from '@razorpay/blade-old/src/atoms/Space';
import { getIsTestMode } from 'merchant/views/PaymentHandle/utils';
import { PAYMENT_INITIAL_VALUE } from 'merchant/views/PaymentHandle/constants';
import Banner from 'merchant/views/PaymentHandle/views/List/components/Banner';
import List from 'merchant/views/PaymentHandle/views/List/components/ListFilter';
import { fetchPaymentPageEntity } from 'merchant/views/PaymentPages/PaymentPages/model';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import lazy from 'merchant/routes/LazyLoader';
import { analyticsTrack, getDeviceSource } from 'common/utils/analytics';
import { isMobileDevice } from 'merchant/components/Home/data';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

const MonetizationChargesBanner = lazy(
  () =>
    import(
      /* webpackChunkName: 'PaymentHandleMonetizationChargesBanner' */ 'merchant/components/Announcements/MonetizationCharges'
    ),
);

const PaymentHandleListFilter: React.FC<PaymentHandleListFilterPropTypes> = ({
  user,
  mode,
  isMobile,
  handleInfo,
}) => {
  const [isTestMode] = useState(getIsTestMode(mode));
  const [paymentHandleEntity, setPaymentPageEntity] =
    useState<PaymentPageEntity>(PAYMENT_INITIAL_VALUE);

  useEffect(() => {
    const getListItems = async (data) => {
      await fetchPaymentPageEntity(data.id).then((paymentHandleData) => {
        setPaymentPageEntity(paymentHandleData.data);
      });
    };
    getListItems(handleInfo.data);
    analyticsTrack({
      objectName: 'NC App Page Dashboard',
      actionName: 'Render Success',
      screen: 'Razorpay.Me Link',
      toCleverTap: true,
      properties: {
        event_name: 'nc_app_.render.success',
        source: getDeviceSource(),
        page: 'Payment Handle',
        email_id: user?.email,
        url: window.location.href,
        browser: window.razorpayAnalytics?.utils?.getBrowserDetails(),
        activation_status: user?.activation_status,
        device_type: isMobileDevice(1020) ? 'mweb' : 'dweb',
        exp_name: 'NoCode Monetization',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
  }, [handleInfo.data.id]);

  return (
    <Space>
      <View>
        <SuspenseWithLoader>
          <MonetizationChargesBanner screen="razorpayMeLink" user={user} userId={user?.current} />
        </SuspenseWithLoader>
        <Banner handleInfo={handleInfo.data} isTestMode={isTestMode} isMobile={isMobile} />
        <List
          isTestMode={isTestMode}
          handleInfo={handleInfo.data}
          paymentPageEntity={paymentHandleEntity}
        />
      </View>
    </Space>
  );
};

export default compose(
  connect((state) => ({
    user: state.session.user,
    mode: state.session.mode,
    isMobile: state.app.isMobileResolution,
    handleInfo: state.paymentHandle.handleInfo,
  })),
)(PaymentHandleListFilter);
