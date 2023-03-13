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

const PaymentHandleListFilter: React.FC<PaymentHandleListFilterPropTypes> = ({
  mode,
  isMobile,
  handleInfo,
}) => {
  const [isTestMode] = useState(getIsTestMode(mode));
  const [paymentHandleEntity, setPaymentPageEntity] = useState<PaymentPageEntity>(
    PAYMENT_INITIAL_VALUE,
  );

  useEffect(() => {
    const getListItems = async (data) => {
      await fetchPaymentPageEntity(data.id).then((paymentHandleData) => {
        setPaymentPageEntity(paymentHandleData.data);
      });
    };
    getListItems(handleInfo.data);
  }, [handleInfo.data.id]);

  return (
    <Space margin={[12, 0, 0, 0]}>
      <View>
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
    mode: state.session.mode,
    isMobile: state.app.isMobileResolution,
    handleInfo: state.paymentHandle.handleInfo,
  })),
)(PaymentHandleListFilter);
