import React, { useEffect } from 'react';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { analyticsTrack } from 'common/utils/analytics';
import ActivationForm from './index';

const ActivationNativeView = () => {
  useEffect(() => {
    analyticsTrack({
      objectName: 'native full view activation form',
      actionName: 'displayed',
      screen: 'KYC Document',
      properties: {
        show_activation_form_full_view: 'true',
        experiment_name: 'show_activation_form_full_view',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
  }, []);

  return <ActivationForm />;
};

export default ActivationNativeView;
