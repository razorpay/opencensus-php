import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import {
  getApplications as getApplicationsAction,
  fetchProducts as fetchProductsAction,
  resetCapitalLendingData as resetCapitalLendingDataAction,
  getApplicationByParamData,
} from 'merchant/reducers/capital';
import { showNotification as showNotificationAction } from 'merchant_common/reducers/notifications';
import { getFormattedApplicationData } from 'merchant/views/Capital/CashAdvanceV2/utils';
import { StatusTrackerPropsT } from 'merchant/components/StatusTracker/statusTracker.types';
import StatusTrackerWrapper from 'merchant/components/StatusTracker/StatusTracker';
import { getXCCStatusTrackerProps } from './xCorporateCardWrapperUtil';
import { CAPITAL_PRODUCT_CODES } from 'merchant/views/Capital/Loans/constants';

const XCCStatusTracker = ({
  user,
  getApplications,
  fetchProducts,
  loanApplicationDetails,
  resetCapitalLendingData,
  showNotification,
}): JSX.Element | null => {
  const productCode = CAPITAL_PRODUCT_CODES.CARDS;

  const [statusTrackerProps, setStatusTrackerProps] = useState<StatusTrackerPropsT | null>(null);

  const getProductId = (): string | null | undefined => {
    return Array.isArray(loanApplicationDetails?.products?.data)
      ? loanApplicationDetails.products.data.find((p) => p?.name === productCode)?.id
      : null;
  };

  const initApplication = async (productId: string) => {
    const applicationResponse = await getApplications({
      owner_type: 'MERCHANT',
      owner_id: user.current,
      product_id: productId,
    });

    const applicationId: string | undefined = applicationResponse?.data?.applications?.[0]?.id;

    try {
      if (applicationId) {
        const detailedApplicationResponse = await getApplicationByParamData({
          application_id: applicationId,
        });

        const applicationStatus = getFormattedApplicationData(
          detailedApplicationResponse?.data || {},
          productCode,
        );

        if (
          applicationStatus?.navigation?.applicationStatus &&
          applicationStatus?.navigation?.current
        ) {
          setStatusTrackerProps(getXCCStatusTrackerProps(applicationStatus.navigation));
        }
      }
    } catch (error) {
      let errorMsg = 'Some error occurred in fetching status data';

      const anyError = error as any;

      if (anyError?.status_code && typeof anyError?.errors?.[0] === 'string')
        errorMsg = `Error ${anyError.status_code} - ${anyError.errors[0]}`;

      showNotification({
        type: 'error',
        message: errorMsg,
      });
    }
  };

  useEffect(() => {
    resetCapitalLendingData();
  }, [resetCapitalLendingData]);

  useEffect(() => {
    fetchProducts();
  }, [fetchProducts]);

  useEffect(() => {
    const productId = getProductId();

    if (productId) initApplication(productId);
  }, [loanApplicationDetails?.products?.loading]);

  if (statusTrackerProps) return <StatusTrackerWrapper {...statusTrackerProps} />;

  return null;
};

const ConnectedXCCStatusTracker = connect(
  (state) => ({ loanApplicationDetails: state.loanApplicationDetails, user: state.session.user }),
  {
    getApplications: getApplicationsAction,
    fetchProducts: fetchProductsAction,
    resetCapitalLendingData: resetCapitalLendingDataAction,
    showNotification: showNotificationAction,
  },
)(XCCStatusTracker);

export default ConnectedXCCStatusTracker;
