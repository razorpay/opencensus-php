import {type Option} from '@libs/web-nexus/common/components/Dropdown/types';
import Dropdown from "@libs/web-nexus/common/components/Dropdown"
import { useMobile, paiseToRupees } from '@libs/shared-utils';
import {
  Amount,
  Box,
  Card,
  CardBody,
  Heading,
  InfoIcon,
  Link,
  RefreshIcon,
  Skeleton,
  Text,
} from '@razorpay/blade/components';
import OverviewContainer from 'apps/self-serve/src/App/Transactions/v2/Analytics/components/OverviewContainer';
import { useDisputesData } from 'apps/self-serve/src/App/Transactions/v2/Analytics/hooks';
import { getOptions } from 'apps/self-serve/src/App/Transactions/v2/Analytics/utils';
import {
  mobileBreakoints,
  TransactionsPagesMap,
} from 'apps/self-serve/src/App/Transactions/v2/common/constants';
import { track } from 'apps/self-serve/src/App/Transactions/v2/common/tracking';
import { Duration, DurationOption } from 'apps/self-serve/src/App/Transactions/v2/common/types';
import { endOfDay, getFromTime } from 'apps/self-serve/src/App/Transactions/v2/common/utils';
import DisputeDistributionGraph from 'apps/self-serve/src/App/Transactions/v2/Disputes/components/DisputeDistributionGraph';
import { DisputeIndicators } from 'apps/self-serve/src/App/Transactions/v2/Disputes/styled';
import { getDisputesOverviewValues } from 'apps/self-serve/src/App/Transactions/v2/Disputes/utils';
import { Currency } from 'apps/self-serve/src/App/Transactions/v2/Payments/types';
import LoadFailedIcon from 'apps/self-serve/src/assets/load-failed.svg';
import React, { useEffect, useState } from 'react';
import { useStore } from '@federated/apps/shell/commonStore';

const DisputeOverview = (): JSX.Element => {
  const isMobile = useMobile(mobileBreakoints);
  const session = useStore((state) => state.session);
  const mode = session.mode;
  const user = session.user;

  const { defaultDate, defaultDuration, durationOptions } = getOptions(isMobile);
  const [duration, setDateDuration] = useState<Duration>(defaultDate);

  const {
    fetchDisputesData,
    disputeData,
    loading: isDisputeDataLoading,
    failed: isDisputeDataFailed,
  } = useDisputesData({
    mode,
  });

  const { openDisputes, underReviewDisputes, lostDisputes, wonDisputes } = disputeData;

  const disputesPhases = getDisputesOverviewValues({
    openDisputesCount: openDisputes.count,
    underReviewDisputesCount: underReviewDisputes.count,
    wonDisputesCount: wonDisputes.count,
    lostDisputesCount: lostDisputes.count,
  });

  disputeData.totalDisputeAmount;

  const refetchApi = () => {
    fetchDisputesData(duration);
  };

  const onDurationChange = ([{ title, value }]: Option[]) => {
    const from = getFromTime(value as DurationOption['value']).unix();
    const to = endOfDay.unix();
    const updatedDuration = {
      from,
      to,
    };
    setDateDuration(updatedDuration);
    track({
      objectName: 'Overview Date',
      properties: {
        overviewDate: title,
        section: TransactionsPagesMap['/disputes'],
        type: 'Overview',
      },
    });
  };

  useEffect(() => {
    fetchDisputesData(duration);
  }, [duration]);

  return (
    <OverviewContainer>
      <Box padding="spacing.3">
        <Box
          display="flex"
          flexDirection="row"
          alignItems="center"
          gap="spacing.2"
          justifyContent="space-between"
        >
          <Box display="flex" flexDirection="row" alignItems="center" gap="spacing.2">
            <Heading weight="semibold" size="small">
              Disputes raised
            </Heading>
            <Dropdown
              onChange={onDurationChange}
              options={durationOptions}
              defaultOptions={[defaultDuration]}
              isDisabled={isDisputeDataLoading}
              bottomSheetTitle="Duration"
              isLink={true}
            />
          </Box>
          <Link icon={RefreshIcon} variant="button" onClick={refetchApi} />
        </Box>
        {isDisputeDataLoading ? (
          <Box display="flex" flexDirection="column" marginTop="spacing.4">
            <Box display="flex" gap="spacing.2" alignItems="center">
              <Text variant="body" weight="semibold" size="medium" color="surface.text.gray.subtle">
                Disputed amount
              </Text>
              <InfoIcon size="small" color="feedback.icon.neutral.intense" />
            </Box>
            <Skeleton borderRadius="large" height="40px" width="180px" marginTop="spacing.3" />
            <Skeleton borderRadius="max" height="24px" maxWidth="520px" marginTop="spacing.5" />
            <Skeleton borderRadius="max" height="16px" width="158px" marginTop="spacing.4" />
          </Box>
        ) : isDisputeDataFailed ? (
          <Card padding="spacing.3" marginY="spacing.5" elevation="none" display="flex">
            <CardBody>
              <Box
                display="flex"
                padding="spacing.5"
                flexDirection="column"
                minWidth="280px"
                minHeight="140px"
                justifyContent="space-evenly"
                alignItems="center"
              >
                <img src={LoadFailedIcon} alt="data load failed" />
                <Box
                  display="flex"
                  justifyContent="center"
                  flexDirection="column"
                  alignItems="center"
                >
                  <Text textAlign="center" variant="body" weight="regular" size="medium">
                    We couldn&apos;t load the summary of your disputes.
                  </Text>
                  <Text variant="body" weight="regular" size="medium">
                    Refresh to try again
                  </Text>
                </Box>
              </Box>
            </CardBody>
          </Card>
        ) : (
          <Box display="flex" flexDirection="column" marginTop="spacing.4">
            <Box display="flex" gap="spacing.2" alignItems="center">
              <Heading color="surface.text.gray.subtle" weight="semibold" size="small">
                Disputed amount
              </Heading>
              <InfoIcon size="small" color="interactive.icon.gray.subtle" />
            </Box>
            {isDisputeDataLoading ? (
              <Skeleton
                borderRadius="large"
                height="40px"
                width="180px"
                marginTop="spacing.3"
                marginBottom="spacing.5"
              />
            ) : (
              <Box
                display="flex"
                alignItems="center"
                gap="spacing.3"
                marginTop="spacing.3"
                marginBottom="spacing.5"
              >
                <Amount
                  size="xlarge"
                  value={paiseToRupees(disputeData.totalDisputeAmount)}
                  weight="semibold"
                  currency={user.merchant?.currency as Currency}
                  type="heading"
                  color="surface.text.gray.normal"
                  isAffixSubtle={false}
                />
                {disputeData.totalDisputeAmount > 0 ? (
                  <Text color="surface.text.gray.subtle" weight="regular" size="medium">
                    from {disputeData.totalDisputesCount} disputes
                  </Text>
                ) : (
                  <Text color="surface.text.gray.subtle" weight="regular" size="medium">
                    no disputes created
                  </Text>
                )}
              </Box>
            )}
            {disputeData.totalDisputeAmount > 0 ? (
              <>
                <DisputeDistributionGraph data={disputeData} />

                <Box display="flex" alignItems="center" gap="spacing.4" marginTop="spacing.4">
                  {disputesPhases.map((phase, index) => {
                    return phase.value ? (
                      <Box
                        display="flex"
                        alignItems="center"
                        gap="spacing.3"
                        key={`${phase}-${index}`}
                      >
                        <DisputeIndicators status={phase.status} />
                        <Text
                          variant="body"
                          weight="regular"
                          size="small"
                          color="surface.text.gray.subtle"
                        >
                          {phase.value} {phase.title}
                        </Text>
                      </Box>
                    ) : null;
                  })}
                </Box>
              </>
            ) : null}
          </Box>
        )}
      </Box>
    </OverviewContainer>
  );
};

export default DisputeOverview;
