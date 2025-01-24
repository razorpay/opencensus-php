import React, { useEffect, useState } from 'react';

import { TEXT_CONTENT } from 'merchant/containers/Home/RTUX/MerchantOverview/constants';
import {
  IMerchantOverview,
  settlementConfig,
} from 'merchant/containers/Home/RTUX/MerchantOverview/types';
import { ErrorState } from 'merchant/widgets/common/ErrorState';
import { useRetryWidget } from 'merchant/widgets/hooks';
import { CommonWidgetProps } from 'merchant/widgets/types';

import NonSettlement from './NonSettlement';
import NonSettlementLoader from './NonSettlement/Loader';
import Settlement from './Settlement';
import SettlementLoader from './Settlement/Loader';
import { getUcsAliasFromQueryKey, track } from 'merchant/widgets/utils';
import { getAnalyticsHeroCardStateIdentifier } from '../utils';
import {
  isSettlementSOHBlockEnabled,
  SETTLEMENT_HOLD_FEATURE,
} from 'merchant/views/Settlements/components/utils';
import { isRiskFoh, isRiskDisabled } from './Settlement/utils';
import SettlementBlockedSOH from './Settlement/SettlementBlockedSOH';
import { useSplitzService } from 'common/splitz';

const MerchantOverviewData: React.FC<IMerchantOverview & CommonWidgetProps> = ({
  queryKey,
  id,
  isLoading,
  error,
  data,
  user,
  type,
  isFOHMerchant = false,
}) => {
  const [bankUpdate, setBankUpdate] = useState(false);
  const [isRetrying, retryHandler] = useRetryWidget(queryKey);
  const screen = getUcsAliasFromQueryKey(queryKey) ?? '';
  const widgetId = `merchantDashboard.${screen}.${type}.${id}`;
  const [settlementConfig, setSettlementConfig] = useState<settlementConfig>({});
  const splitz = useSplitzService();
  const isRiskNonTransactedFoh =
    isRiskFoh() &&
    (data?.hero_card_data?.is_transacted === false ||
      data?.hero_card_data?.is_settlement === false);

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

  useEffect(() => {
    if (data) {
      const bankAccountUpdate = data?.hero_card_data?.settlement?.bank_update_status;
      setBankUpdate(bankAccountUpdate ?? false);

      const features = data?.hero_card_data?.settlement?.settlement_config_details?.features;
      let updatedConfig = {};
      if (isRiskNonTransactedFoh) {
        updatedConfig = {
          source: SETTLEMENT_HOLD_FEATURE.FOH,
          status: true,
        };
      } else if (isRiskDisabled() && !isRiskFoh()) {
        updatedConfig = {
          source: SETTLEMENT_HOLD_FEATURE.DISABLED_LIVE,
          status: true,
          sub_title: 'We have disabled your account.',
        };
      } else if (features?.global_hold_config) {
        updatedConfig = {
          source: SETTLEMENT_HOLD_FEATURE.FOH,
          status: true,
          sub_title: features?.global_hold_config_sub_title,
          cta_text: features?.global_hold_config_cta_text,
          cta_link: features?.global_hold_config_cta_url,
        };
      } else if (features?.hold) {
        updatedConfig = {
          source: SETTLEMENT_HOLD_FEATURE.HOLD,
          status: true,
          sub_title: features?.hold_sub_title,
          cta_text: features?.hold_cta_text,
          cta_link: features?.hold_cta_url,
        };
      } else if (features?.block) {
        updatedConfig = {
          source: SETTLEMENT_HOLD_FEATURE.BLOCK,
          status: true,
          sub_title: features?.block_sub_title,
          cta_text: features?.block_cta_text,
          cta_link: features?.block_cta_url,
        };
      }
      setSettlementConfig(updatedConfig);
    }
  }, [data]);

  if (isLoading || isRetrying)
    return user.isTransacted ? (
      <SettlementLoader isRiskFohMerchant={isFOHMerchant} />
    ) : (
      <NonSettlementLoader isRiskFohMerchant={isFOHMerchant} />
    );

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

  if (isSettlementSOHBlockEnabled(splitz) && (isRiskNonTransactedFoh || isRiskDisabled())) {
    return (
      <SettlementBlockedSOH
        settlementConfig={settlementConfig}
        bankUpdate={false}
        isFohRiskDisabled={isRiskDisabled() && !isRiskFoh()}
      />
    );
  }

  return isSettlement && settlement ? (
    <Settlement
      settlement={settlement}
      bankUpdate={bankUpdate}
      settlementConfig={settlementConfig}
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
