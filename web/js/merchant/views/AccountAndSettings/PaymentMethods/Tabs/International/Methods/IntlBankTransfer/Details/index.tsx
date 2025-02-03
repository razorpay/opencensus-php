import React, { useEffect } from 'react';
import {
  Alert,
  Accordion,
  AccordionItem,
  Box,
  Text,
  AccordionItemBody,
  AccordionItemHeader,
  Avatar,
  Link,
  Skeleton,
} from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';

import ErrorBoundary, { Teams, Ranks } from 'common/new-ui/ErrorBoundary';
import { CommonInstrumentRequestInfo, LeafListItem } from 'common/typings';
import copyToClipboard from 'common/utils/copyToClipboard';
import { merchantFetch } from 'merchant/utils/ajax';
import { track } from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/analytics/track';

import AccountRow from './AccountItemRow';
import { ACCOUNT_DETAIL_FIELD_MAPPING, RAZORPAY_SUPPORT_LINK, ACCOUNTS_STATUS } from './constants';

type AccountType = {
  va_currency: string;
  routing_code: string;
  routing_type: string;
  account_number: string;
  beneficiary_name: string;
  bank_name: string;
  bank_address: string;
  status: string;
};

type DetailsProps = {
  item: LeafListItem;
  isLoading: boolean;
  onAccountActivate: (currency: string) => void;
};

const Details = ({ item, isLoading, onAccountActivate }: DetailsProps) => {
  const { data, isLoading: isLoadingAccounts } = useQuery({
    queryKey: ['international_virtual_accounts'],
    queryFn: () =>
      merchantFetch({
        url: 'international/virtual_accounts',
        mode: 'live',
      }),
    refetchOnWindowFocus: false,
  });

  const { accounts, status } = data?.data ?? {};
  const isAllAccountDeactivated =
    status === ACCOUNTS_STATUS.DEACTIVATED ||
    accounts?.length === 0 ||
    isLoading ||
    isLoadingAccounts;

  const handleCopy = (evt: React.SyntheticEvent, account: AccountType) => {
    evt?.stopPropagation();

    if (!isAllAccountDeactivated) {
      copyToClipboard(
        ACCOUNT_DETAIL_FIELD_MAPPING.map((field) => {
          return `${field.label} = ${account[field.key] ?? '--'}`;
        }).join('\n'),
      );
    }

    track('clicked', { objectName: 'details copy all' });
  };

  useEffect(() => {
    track('render', { objectName: 'details' });
  }, []);

  if (!item) {
    return null;
  }

  return (
    <ErrorBoundary rank={Ranks.P1} team={Teams.CROSS_BORDER} resetOnProps>
      <Box
        display="flex"
        flexDirection="column"
        gap="spacing.7"
        position="relative"
        padding={{
          base: 'spacing.5',
          l: 'spacing.7',
        }}
        borderRadius="medium"
        backgroundColor="surface.background.gray.moderate"
      >
        <Box>
          <Text
            size="large"
            color={isAllAccountDeactivated ? 'feedback.text.neutral.subtle' : undefined}
          >
            {item.listHeader}
          </Text>
          <Text color={isAllAccountDeactivated ? 'feedback.text.neutral.subtle' : undefined}>
            {item.listDescription}
          </Text>
        </Box>

        <Accordion variant="filled" maxWidth="100%">
          {item.list?.map((listItem) => {
            const vaCurrency = (
              listItem as CommonInstrumentRequestInfo & {
                vaCurrency: string;
              }
            ).vaCurrency;

            const account = accounts?.find((acc) => acc.va_currency === vaCurrency);

            const isSingleAccountDeactivated = account?.status === ACCOUNTS_STATUS.DEACTIVATED;

            const canRequestActivation = !isAllAccountDeactivated && !account && !isLoading;

            const canCopyDetails =
              !!account && !isAllAccountDeactivated && !isSingleAccountDeactivated && !isLoading;

            return (
              <AccordionItem
                key={listItem.slug}
                isDisabled={isAllAccountDeactivated || isSingleAccountDeactivated || isLoading}
                testID={listItem.slug}
              >
                <AccordionItemHeader
                  title={listItem.name}
                  subtitle={listItem.description}
                  key={account?.va_currency}
                  leading={
                    <Avatar
                      size="small"
                      src={listItem.icon}
                      name={listItem.name}
                      alt={listItem.name}
                    />
                  }
                  trailing={
                    canRequestActivation ? (
                      <Link
                        onClick={() => onAccountActivate(vaCurrency)}
                        variant="button"
                        testID="request-activation"
                      >
                        Request
                      </Link>
                    ) : canCopyDetails ? (
                      <Link
                        onClick={(evt) => handleCopy(evt, account)}
                        variant="button"
                        testID="copy-details"
                      >
                        Copy details
                      </Link>
                    ) : null
                  }
                />
                <AccordionItemBody>
                  <Box>
                    {isLoading && (
                      <Box
                        padding="spacing.5"
                        borderRadius="medium"
                        backgroundColor="surface.background.gray.moderate"
                        testID="account-loader"
                      >
                        <Skeleton height="30px" marginBottom="spacing.2" />
                        <Skeleton height="30px" marginBottom="spacing.2" />
                        <Skeleton height="30px" marginBottom="spacing.2" />
                        <Skeleton height="30px" marginBottom="spacing.2" />
                        <Skeleton height="30px" />
                      </Box>
                    )}

                    {!isLoading && (
                      <>
                        {isSingleAccountDeactivated && (
                          <Alert
                            isFullWidth
                            color="information"
                            isDismissible={false}
                            title={`Your ${listItem.name} has been deactivated`}
                            description={
                              <Text>
                                To activate your account, please{' '}
                                <Link href={RAZORPAY_SUPPORT_LINK} target="_blank" rel="noopener">
                                  contact our support team
                                </Link>
                              </Text>
                            }
                          />
                        )}
                        {!isSingleAccountDeactivated &&
                          account &&
                          ACCOUNT_DETAIL_FIELD_MAPPING.map((field, idx) => {
                            return (
                              <Box
                                key={field.key}
                                paddingX="spacing.5"
                                paddingY="spacing.1"
                                borderRadius="medium"
                                backgroundColor="surface.background.gray.moderate"
                              >
                                <AccountRow
                                  label={field.label}
                                  value={account[field.key]}
                                  showDivider={idx < ACCOUNT_DETAIL_FIELD_MAPPING.length - 1}
                                />
                              </Box>
                            );
                          })}
                      </>
                    )}
                  </Box>
                </AccordionItemBody>
              </AccordionItem>
            );
          })}
        </Accordion>
      </Box>
    </ErrorBoundary>
  );
};

export default Details;
