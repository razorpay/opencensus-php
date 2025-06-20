import React from 'react';
import {
  Box,
  StepGroup,
  StepItem,
  ArrowRightIcon,
  Link,
  Text,
  Heading,
  Amount,
} from '@razorpay/blade/components';
import moment from 'moment';
import { i18CurrencyConversionFromMinorUnitToCommonUnit, isMobileDevice } from '@libs/shared-utils';
import CompletedIcon from './assets/TransactionCompletedIcon.svg';
import PendingIcon from './assets/TransactionPendingIcon.svg';
import UpcomingIcon from './assets/TransactionUpcomingIcon.svg';
import StepperWhitebg from './assets/StepperWhiteBase.png';
import StepperWhitebgMobile from './assets/StepperWhiteBaseMobile.png';
import StepperBlueBg from './assets/SteppedBlueBg.png';
import StepperBlueBgMobile from './assets/SteppedBlueBgMobile.png';
import DesktopTransactionStep from './DesktopTransactionStep';
import SettlementStatusInfo from './SettlementStatusInfo';
import { getDateSuffix } from '@OnboardingExperienceCommons/utils/dateAndTime';
import { IMerchantPayments, ISettlementData } from '@federated/dashboards/payments/types/payments';
import { Wrapper } from 'apps/onboarding-experience/src/container';
import { analyticsTrackWithUserInfo } from '@libs/shared-utils';

const defaultDateFormat = 'Do MMM';

const TransactionTimeline = ({
  transactions,
  settlementData,
}: {
  transactions: IMerchantPayments[];
  settlementData?: ISettlementData;
}) => {
  const isMobile = isMobileDevice();
  const transactionCount = transactions.length;
  const lastTransaction = transactions[transactionCount - 1];

  return (
    <Wrapper>
      <Box
        backgroundImage={`url(${isMobile ? StepperBlueBgMobile : StepperBlueBg})`}
        backgroundRepeat="round"
        backgroundColor="surface.background.gray.moderate"
        display="flex"
        flexDirection="column"
        gap="spacing.5"
        paddingX={{
          base: 'spacing.5',
          l: '72px',
        }}
        paddingY="spacing.5"
      >
        {isMobile ? (
          <Text weight="semibold" color="surface.text.staticWhite.normal">
            Your first 5 transactions
          </Text>
        ) : (
          <Heading size="large" weight="semibold" color="surface.text.staticWhite.normal">
            Your first 5 transactions
          </Heading>
        )}
        {/* Timeline */}
        <Box
          display="flex"
          flexDirection={{
            base: 'column',
            m: 'row',
          }}
          alignItems="center"
          justifyContent="center"
          backgroundImage={`url(${isMobile ? StepperWhitebgMobile : StepperWhitebg})`}
          backgroundRepeat="round"
          paddingX={{
            base: 'spacing.5',
            m: '32px',
          }}
          paddingY={{
            base: 'spacing.4',
            m: '24px',
          }}
          paddingBottom="spacing.5"
          marginRight="-12px"
          width="100%"
          elevation="highRaised"
        >
          <StepGroup orientation="horizontal">
            {Array.from({ length: 5 }).map((_, index) => {
              const isCompleted = index < transactionCount;
              const isUpcoming = index === transactionCount;
              const icon = isCompleted ? CompletedIcon : isUpcoming ? UpcomingIcon : PendingIcon;
              return (
                <StepItem
                  key={index}
                  minWidth="spacing.2"
                  marker={
                    <Box
                      width={isMobile ? '100%' : isUpcoming ? '70px' : 'spacing.10'}
                      height="spacing.8"
                      display="flex"
                      alignItems="center"
                      justifyContent="center"
                    >
                      <img
                        src={icon}
                        alt="Completed"
                        width={isMobile ? '32px' : '48px'}
                        height={isMobile ? '32px' : '48px'}
                      />
                    </Box>
                  }
                  stepProgress={isCompleted ? 'full' : 'none'}
                  title={''}
                >
                  <Box marginTop={{ base: '-16px', m: '-8px' }}>
                    {isMobile ? (
                      <Text
                        size="small"
                        weight="semibold"
                        color={
                          isCompleted ? 'surface.text.onSea.onSubtle' : 'surface.text.gray.muted'
                        }
                      >{`#${index + 1}`}</Text>
                    ) : (
                      <DesktopTransactionStep
                        transaction={transactions[index]}
                        step={index + 1}
                        isUpcoming={isUpcoming}
                        isCompleted={isCompleted}
                      />
                    )}
                  </Box>
                </StepItem>
              );
            })}
          </StepGroup>
          {isMobile && (
            <>
              {transactionCount === 0 && (
                <Text size="small" color="surface.text.gray.subtle">
                  Your first transaction is pending
                </Text>
              )}
              {transactionCount > 0 && (
                <Text size="small" color="surface.text.gray.subtle" textAlign="center">
                  {transactionCount}
                  {getDateSuffix(transactionCount)} Transaction of{' '}
                  <Amount
                    suffix="decimals"
                    currency={lastTransaction.currency}
                    value={i18CurrencyConversionFromMinorUnitToCommonUnit(
                      Number(lastTransaction.amount),
                      lastTransaction.currency,
                    )}
                    size="small"
                    type="body"
                    weight="regular"
                    color="surface.text.gray.subtle"
                    isAffixSubtle={false}
                  />{' '}
                  on {moment.unix(Number(lastTransaction.createdAt)).format(defaultDateFormat)}{' '}
                  <Link
                    size="small"
                    icon={ArrowRightIcon}
                    iconPosition="right"
                    href={`/app/payments/${lastTransaction.id}`}
                    target="_blank"
                    onClick={() => {
                      analyticsTrackWithUserInfo({
                        objectName: 'FTUX Last Transaction Link',
                        actionName: 'Clicked',
                        screen: 'home page',
                      });
                    }}
                  >
                    View
                  </Link>
                </Text>
              )}
            </>
          )}
        </Box>
        {/* Settlement Details */}
        <SettlementStatusInfo settlementData={settlementData} />
      </Box>
    </Wrapper>
  );
};

export default TransactionTimeline;
