import { fetchItem as fetchItemAction } from 'merchant/reducers/settlements/details';
import { showNotification as showNotificationAction } from 'merchant_common/reducers/notifications';
import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import SettlementDetailsRevampLayout from './components/Layout/LayoutRevamp';
import ErrorScreen from './screens/ErrorScreen';
import SettlementDetailRevampView from './screens/SettlementDetailView/SettlementDetailViewRevamp';
import { FullPageRevampShimmer } from './screens/Shimmer/Shimmer';
import { ERROR_TYPE, SettlementDetailsInterface } from './typings';
import { getErrorType } from './utils/common';

const SettlementDetails = ({
  match,
  error,
  loading,
  fetchItem,
  showNotification,
}: SettlementDetailsInterface): JSX.Element => {
  const {
    params: { id: settlementId },
  } = match;
  const [errorType, setErrorType] = useState<ERROR_TYPE>(ERROR_TYPE.SERVER_ERROR);

  const fetchItemByID = (settlementId) => {
    fetchItem(settlementId).catch(({ errors }): void => {
      setErrorType(getErrorType(errors[0] || ''));
      showNotification({
        type: 'error',
        message: errors[0] || 'Something went wrong, Please try again later',
      });
    });
  };

  useEffect((): void => {
    fetchItemByID(settlementId);
  }, [settlementId]);

  const handleRefresh = () => fetchItemByID(settlementId);

  return (
    <SettlementDetailsRevampLayout settlementId={settlementId}>
      {loading ? (
        <FullPageRevampShimmer />
      ) : error ? (
        <ErrorScreen handleRefresh={handleRefresh} type={errorType} isDetailsRevampFlow={true} />
      ) : (
        <SettlementDetailRevampView settlementId={settlementId} />
      )}
    </SettlementDetailsRevampLayout>
  );
};

const mapStateToProps = (state) => {
  const { settlement } = state;
  return {
    settlement: settlement.settlement,
    loading: settlement.loading,
    error: settlement.error,
  };
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    { fetchItem: fetchItemAction, showNotification: showNotificationAction },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(SettlementDetails);
