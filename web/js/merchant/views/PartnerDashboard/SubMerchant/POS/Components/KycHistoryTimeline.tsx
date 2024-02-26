import React from 'react';
import { Box, Text } from '@razorpay/blade/components';

import { POS_ACTIVATION_STATUS } from 'merchant/views/POS/types';
import {
  parsedActionStatesType,
  parsedClarificationReasonsType,
} from 'merchant/views/PartnerDashboard/SubMerchant/POS/TypeDeclares';
import { parseKycHistoryData } from 'merchant/views/PartnerDashboard/SubMerchant/POS/utils';

import { TimelineStatus } from './TimeLineStatus';

type KycHistoryTimelineProps = {
  actionState: parsedActionStatesType | undefined;
  clarificationReasons: parsedClarificationReasonsType | undefined;
};

export const KycHistoryTimeline = ({
  actionState,
  clarificationReasons,
}: KycHistoryTimelineProps): JSX.Element => {
  const data = parseKycHistoryData(actionState, clarificationReasons);

  const getStatus = (status: string) => {
    switch (status) {
      case POS_ACTIVATION_STATUS.submitted:
        return 'Submitted';
      case POS_ACTIVATION_STATUS.needs_clarification:
        return 'Needs Clarification';
      case POS_ACTIVATION_STATUS.rejected:
        return 'Rejected';
      case POS_ACTIVATION_STATUS.under_review:
        return 'Under Review';
      case POS_ACTIVATION_STATUS.activated:
        return 'Activated';
      default:
        return 'Submitted';
    }
  };
  return (
    <Box>
      <Text weight="bold" color="surface.text.subtle.lowContrast">
        KYC History:
      </Text>
      {data.length ? (
        <Box paddingY="spacing.5">
          {data.map((item, index) => {
            return (
              <Box key={`${item.status}-${index}`}>
                <Box display="flex" justifyContent="space-between" alignItems="center">
                  <Box display="flex">
                    <TimelineStatus status={item.status} />
                    <Text weight="bold">{getStatus(item.status)}</Text>
                  </Box>
                  <Text size="small" color="surface.text.placeholder.lowContrast">
                    {item.date || 'N/A'}
                  </Text>
                </Box>
                <Box
                  marginY="spacing.2"
                  marginX="spacing.3"
                  borderLeftWidth={index === data.length - 1 ? 'none' : 'thin'}
                  borderLeftColor="surface.border.normal.lowContrast"
                  paddingX="spacing.7"
                  paddingBottom="spacing.6"
                  paddingTop="spacing.2"
                >
                  <Text size="small">KYC Performed by : {item.performedBy || 'N/A'}</Text>
                  {item.reason ? (
                    <>
                      <Text>Issues:</Text>
                      <Box display="flex" flexDirection="column" marginLeft="spacing.6">
                        {item.reason.map((reason, index) => {
                          return (
                            <Text size="small" key={`${reason.field}-${index}`}>
                              {reason.field}: {reason.reason}
                            </Text>
                          );
                        })}
                      </Box>
                    </>
                  ) : null}
                </Box>
              </Box>
            );
          })}
        </Box>
      ) : (
        <Text>N/A</Text>
      )}
    </Box>
  );
};
