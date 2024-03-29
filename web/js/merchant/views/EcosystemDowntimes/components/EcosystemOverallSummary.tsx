import React, { useContext } from 'react';
import { EcosystemDowntimeContext } from 'merchant/views/EcosystemDowntimes/context';
import {
  EcosystemOverallSummaryContainer,
  EcosystemStatusIconContainer,
} from 'merchant/views/EcosystemDowntimes/styles';
import { toTitleCase } from 'common/utils';
import { METHOD_NAMES_MAP, STATUS } from 'merchant/views/EcosystemDowntimes/constants';
import { ActivityIcon, Text } from '@razorpay/blade/components';

const EcosystemOverallSummary = (): JSX.Element => {
  const { state } = useContext(EcosystemDowntimeContext);
  const { activeDowntimes } = state;
  const downtimesForMethods = Object.keys(activeDowntimes || {});
  const hasActiveDowntime = !!downtimesForMethods.length;

  return (
    <EcosystemOverallSummaryContainer data-testid="ecosystem-overall-summary-container">
      <div className="icon-container">
        {hasActiveDowntime ? (
          <EcosystemStatusIconContainer height={16} width={16} bgColorKey="neutral">
            <ActivityIcon color="feedback.icon.positive.intense" size="small" />
          </EcosystemStatusIconContainer>
        ) : (
          STATUS.operational.icon
        )}
      </div>
      <div className="text-container" aria-label="overall-summary-text">
        {hasActiveDowntime ? (
          <Text size="medium" variant="body" weight="semibold" color="surface.text.gray.normal">
            Few drops noticed in{' '}
            {downtimesForMethods
              .map((method) => METHOD_NAMES_MAP?.[method] || toTitleCase(method))
              .join(', ')}
          </Text>
        ) : (
          <Text size="medium" variant="body" weight="semibold" color="surface.text.gray.normal">
            All methods are operational
          </Text>
        )}
      </div>
    </EcosystemOverallSummaryContainer>
  );
};

export default EcosystemOverallSummary;
