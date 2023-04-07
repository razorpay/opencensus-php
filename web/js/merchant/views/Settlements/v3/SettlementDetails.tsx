import { fetchItem as fetchItemAction } from 'merchant/reducers/settlements/details';
import { showNotification as showNotificationAction } from 'merchant_common/reducers/notifications';
import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import SettlementDetailsLayout from './components/Layout';
import ErrorScreen from './screens/ErrorScreen';
import SettlementDetailView from './screens/SettlementDetailView';
import FullPageShimmer from './screens/Shimmer';
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
    <SettlementDetailsLayout settlementId={settlementId}>
      {loading ? (
        <FullPageShimmer />
      ) : error ? (
        <ErrorScreen handleRefresh={handleRefresh} type={errorType} />
      ) : (
        <SettlementDetailView settlementId={settlementId} />
      )}
    </SettlementDetailsLayout>
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
