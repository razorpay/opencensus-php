import React, { useEffect } from 'react';
import type { RouteComponentProps } from 'common/deprecated/RouteComponentProps';
import { connect } from 'react-redux';
import { compose } from 'redux';
import GrowthAssetEB from 'common/ui/GrowthAssetEB';
import { withRouter } from 'common/deprecated/withRouter';
import { Spinner } from '@razorpay/blade/components';
import { fallbackViewData } from './fallbackViewData';
import Onboarding from 'merchant/views/RazorpayXWidget/Onboarding';
import { fetchXBankingWidget as fetchXBankingWidgetProp } from 'merchant/reducers/growthService';

interface XBankingWidget extends RouteComponentProps {
  fetchXBankingWidget: ({ fromWhere }: { fromWhere: string }) => void;
  x_banking_widgets: {
    loading: boolean;
    x_banking_widgets: Array<Record<string, unknown>>;
  };
}

const XBankingWidget = ({ fetchXBankingWidget, x_banking_widgets, location }: XBankingWidget) => {
  useEffect(() => {
    fetchXBankingWidget({ fromWhere: location.pathname });
  }, [location.pathname]);

  const { x_banking_widgets: xBankingWidgetsData, loading: isLoading } = x_banking_widgets;

  if (isLoading) {
    return (
      <div className="x-banking-spinner">
        <Spinner accessibilityLabel="x-banking-spinner" />
      </div>
    );
  }
  if (xBankingWidgetsData?.length) {
    return (xBankingWidgetsData as any[]).map((x_banking_widget) => (
      <Onboarding key={x_banking_widget?.id} x_banking_widget={x_banking_widget} />
    ));
  }
  return (fallbackViewData?.x_banking_widgets as any[]).map((x_banking_widget) => (
    <Onboarding key={x_banking_widget?.id} x_banking_widget={x_banking_widget} />
  ));
};

const XBankingWidgetWithCompose = compose<any>(
  withRouter,
  connect(
    (state) => ({
      ...(state?.growthService || []),
    }),
    {
      fetchXBankingWidget: fetchXBankingWidgetProp,
    },
  ),
)(XBankingWidget);

const XBankingWidgetWrapper = (): JSX.Element => {
  return (
    <GrowthAssetEB>
      <XBankingWidgetWithCompose />
    </GrowthAssetEB>
  );
};

export default XBankingWidgetWrapper;
