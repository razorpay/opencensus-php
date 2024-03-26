import React, { useEffect } from 'react';

import { TEXT_CONTENT } from 'merchant/containers/Home/RTUX/MerchantOverview/constants';
import { IMerchantOverview } from 'merchant/containers/Home/RTUX/MerchantOverview/types';
import { ErrorState } from 'merchant/widgets/common/ErrorState';
import { useRetryWidget } from 'merchant/widgets/hooks';
import { CommonWidgetProps } from 'merchant/widgets/types';

import NonSettlement from './NonSettlement';
import NonSettlementLoader from './NonSettlement/Loader';
import Settlement from './Settlement';
import SettlementLoader from './Settlement/Loader';
import { getUcsAliasFromQueryKey, track } from 'merchant/widgets/utils';
import { getAnalyticsHeroCardStateIdentifier } from '../utils';

const MerchantOverviewData: React.FC<IMerchantOverview & CommonWidgetProps> = ({
  queryKey,
  id,
  isLoading,
  error,
  data,
  user,
  type,
}) => {
  const [isRetrying, retryHandler] = useRetryWidget(queryKey);
  const screen = getUcsAliasFromQueryKey(queryKey) ?? '';
  const widgetId = `merchantDashboard.${screen}.${type}.${id}`;

  useEffect(() => {
    if (!isLoading && !isRetrying) {
      const properties = {
        actionBy: widgetId,
        widgetId,
      };
      track({
        objectName: 'widget',
        actionName: error ? 'error' : 'loaded',
        screen,
        properties: error
          ? { ...properties, error: `${error.message}` }
          : {
              ...properties,
              title: getAnalyticsHeroCardStateIdentifier(data?.hero_card_data),
            },
      });
    }
  }, [isRetrying, isLoading, error]);

  if (isLoading || isRetrying)
    return user.isTransacted ? <SettlementLoader /> : <NonSettlementLoader />;

  if (error)
    return (
      <ErrorState
        text={TEXT_CONTENT.ERROR_STATE_TITLE}
        retryHandler={() => retryHandler({ id })}
        analyticsProperties={{
          screen,
          error: `${error.message}`,
          actionBy: widgetId,
          widgetId,
        }}
      />
    );

  /* istanbul ignore next */
  if (!data) {
    return null;
  }
  const { hero_card_data } = data;
  const {
    is_settlement: isSettlement,
    is_transacted: isTransacted,
    settlement_schedule: settlementSchedule,
    settlement,
  } = hero_card_data;

  const settlementState = getAnalyticsHeroCardStateIdentifier(hero_card_data);

  return isSettlement && settlement ? (
    <Settlement
      settlement={settlement}
      analyticsProperties={{ settlementState, screen, widgetId }}
    />
  ) : (
    <NonSettlement
      is_transacted={isTransacted}
      settlement_schedule={settlementSchedule}
      analyticsProperties={{ settlementState, screen, widgetId }}
    />
  );
};

export default MerchantOverviewData;
