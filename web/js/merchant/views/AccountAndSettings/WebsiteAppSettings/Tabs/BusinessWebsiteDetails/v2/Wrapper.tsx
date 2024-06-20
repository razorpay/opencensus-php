import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { Environments, ShowNotificationType } from 'common/typings';
import { showNotification as showNotificationReducer } from 'merchant_common/reducers/notifications';

import BusinessWebsiteDetails from './BusinessWebsiteDetails';
import { BusinessWebsiteAutomationContext } from './context';
import { useGetWebsiteUpdate } from './hooks/useGetWebsiteData';
import { trackBusinessWebsitePageLoad } from './tracking';
import { WebsiteSubmitModalSteps } from './types';

interface BusinessWebsiteDetailsWrapperProps {
  mode: Environments;
  showNotification: ShowNotificationType;
}

const BusinessWebsiteDetailsWrapper: React.FC<BusinessWebsiteDetailsWrapperProps> = ({
  mode,
  showNotification,
}) => {
  const [currentStep, setCurrentStep] = useState<WebsiteSubmitModalSteps>(
    WebsiteSubmitModalSteps.ADD_MAIN_PAGE,
  );

  const {
    data: websiteUpdateData,
    isLoading: isWebsiteDetailsFetching,
    refetch: refetchWebsiteUpdateData,
    isError: isWebsiteDetailsFetchError,
    error,
  } = useGetWebsiteUpdate(mode);

  useEffect(() => {
    trackBusinessWebsitePageLoad();
  }, []);

  useEffect(() => {
    if (isWebsiteDetailsFetchError)
      showNotification({
        type: 'error',
        /* @ts-expect-error error-message-check */
        message: error?.message || 'Something went wrong. Please try again.',
      });
  }, [isWebsiteDetailsFetchError, error]);

  return (
    <BusinessWebsiteAutomationContext.Provider
      value={{
        currentStep,
        setCurrentStep,
        websiteUpdateData,
        refetchWebsiteUpdateData,
        isWebsiteDetailsFetching,
        isWebsiteDetailsFetchError,
      }}
    >
      <BusinessWebsiteDetails />
    </BusinessWebsiteAutomationContext.Provider>
  );
};

const mapStateToProps = (state) => {
  return {
    mode: state.session.mode,
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      showNotification: showNotificationReducer,
    },
    dispatch,
  );
};

export default connect(mapStateToProps, mapDispatchToProps)(BusinessWebsiteDetailsWrapper);
