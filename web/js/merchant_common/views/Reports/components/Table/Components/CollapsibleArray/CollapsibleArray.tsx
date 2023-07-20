import React, { useRef, useState } from 'react';
import { Clickable } from 'merchant_common/views/Reports/components/styled';
import { CollapsibleArrayPropsType } from 'merchant_common/views/Reports/components/Table/types';
import { Counter, Heading } from 'merchant_common/views/Reports/components';
import { Email, ListEmails, ListHeader } from './styled';
import { TableText } from 'merchant_common/views/Reports/components/Table/Components/TableText';
import { useClickOutSide, useTheme } from 'merchant_common/views/Reports/hooks';
import { trackDownloadsSection } from 'merchant_common/views/Reports/configs/analytics.config';
import { useDashboardType } from 'merchant_common/views/Reports/contexts/ReportsContext';
import { DashboardType } from 'merchant_common/views/Reports/types';

export const CollapsibleArray = ({
  arr = [],
  logId,
  scheduleId,
}: CollapsibleArrayPropsType): JSX.Element => {
  const { theme } = useTheme();
  const ref = useRef<HTMLDivElement | null>(null);
  const [shouldExpand, setExpand] = useState(false);
  const dashboardType = useDashboardType() as DashboardType;

  useClickOutSide([ref], () => {
    setExpand(false);
  });

  const handleExpand = () => {
    trackDownloadsSection({
      actionName: 'Expand Recipient Emails Click',
      properties: logId
        ? {
            log_id: logId,
          }
        : {
            schedule_id: scheduleId,
          },
      dashboardType,
    });
    setExpand(true);
  };

  return (
    <div ref={ref}>
      {arr && (
        <div
          style={{
            display: 'flex',
            justifyContent: 'space-between',
            alignItems: 'center',
          }}
        >
          <TableText>{arr[0]}</TableText>
          <div
            style={{
              position: 'relative',
            }}
          >
            {arr?.length > 1 ? (
              <Clickable
                style={{
                  marginLeft: 10,
                }}
                onClick={handleExpand}
              >
                <Counter
                  contrast="high"
                  variant="neutral"
                  size="medium"
                  value={10000}
                  max={arr.length - 1}
                />
              </Clickable>
            ) : null}
            {shouldExpand ? (
              <ListEmails theme={theme}>
                <ListHeader theme={theme}>
                  <Heading size="medium">Recipient's Addresses</Heading>
                </ListHeader>

                {arr.slice(1).map((el) => (
                  <Email key={el} theme={theme}>
                    <TableText>{el}</TableText>
                  </Email>
                ))}
              </ListEmails>
            ) : null}
          </div>
        </div>
      )}
    </div>
  );
};
