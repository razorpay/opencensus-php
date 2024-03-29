import React, { useContext, useMemo, useState } from 'react';
import { EcosystemDowntimeContext } from 'merchant/views/EcosystemDowntimes/context';
import {
  groupAllDowntimesByMethod,
  getDowntimeHeaderAndDescription,
} from 'merchant/views/EcosystemDowntimes/helpers';
import { EcosystemMethodSummaryContainer } from 'merchant/views/EcosystemDowntimes/styles';
import { STATUS } from 'merchant/views/EcosystemDowntimes/constants';
import { ChevronDownIcon, ChevronUpIcon, Text } from '@razorpay/blade/components';
import type { EcosystemMethodSummaryType } from 'merchant/views/EcosystemDowntimes/types';

const EcosystemMethodSummary = ({
  method,
  maxToShow = 1,
}: EcosystemMethodSummaryType): JSX.Element => {
  const [isExpanded, setIsExpanded] = useState<boolean>(false);
  const {
    state: { activeDowntimes },
  } = useContext(EcosystemDowntimeContext);

  const groupedDowntimes = useMemo(
    () => groupAllDowntimesByMethod(activeDowntimes, method),
    [activeDowntimes, method],
  );

  if (!activeDowntimes?.[method])
    return (
      <EcosystemMethodSummaryContainer aria-label="ecosystem-method-summary">
        <div className="downtime-summary-item">
          <div className="downtime-summary-header-container">
            {STATUS.operational.icon}{' '}
            <Text size="small" weight="semibold">
              All instruments are functional
            </Text>
          </div>
          <small>No ongoing downtimes</small>
        </div>
      </EcosystemMethodSummaryContainer>
    );

  let processedDowntimes = groupedDowntimes.map((downtime) =>
    getDowntimeHeaderAndDescription(downtime),
  );
  if (!isExpanded) processedDowntimes = processedDowntimes.slice(0, maxToShow);
  const isExpansionRequired = groupedDowntimes.length > 1;
  const handleOnShowClick = () => setIsExpanded(!isExpanded);

  return (
    <EcosystemMethodSummaryContainer aria-label="ecosystem-method-summary" isExpanded={isExpanded}>
      <ul>
        {processedDowntimes.map(({ icon, heading, description }, index) => (
          <li role="listitem" key={`${index}_heading`}>
            <div className="downtime-summary-item">
              <div
                className="downtime-summary-header-container"
                aria-label="method-summary-heading"
              >
                {icon}
                <Text size="small" weight="semibold">
                  {heading}
                </Text>
              </div>
              <small>{description}</small>
            </div>
          </li>
        ))}
      </ul>
      {isExpansionRequired ? (
        <div className="downtime-summary-footer">
          {isExpanded ? (
            <div data-testid="show-less-summary" onClick={handleOnShowClick}>
              <small>Show less</small>
              <ChevronUpIcon color="interactive.icon.primary.normal" size="medium" />
            </div>
          ) : (
            <>
              <small>{`+${groupedDowntimes.length - 1} others`}</small>
              <div data-testid="show-more-summary" onClick={handleOnShowClick}>
                <small>Show other downtimes </small>
                <ChevronDownIcon color="interactive.icon.primary.normal" size="medium" />
              </div>
            </>
          )}
        </div>
      ) : null}
    </EcosystemMethodSummaryContainer>
  );
};

export default EcosystemMethodSummary;
