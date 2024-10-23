import React, { useState, useEffect, Suspense } from 'react';
import {
  Button,
  Box,
  Text,
  Heading,
  Amount,
  HelpCircleIcon,
  Tooltip,
  TextInput,
  Link,
  ChevronDownIcon,
  ChevronUpIcon,
  Collapsible,
  CollapsibleBody,
  Divider,
  Tabs,
  TabList,
  TabItem,
  TabPanel,
  ProgressBar,
  Skeleton,
  Modal,
  ModalBody,
  IconButton,
  CloseIcon,
} from '@razorpay/blade/components';
import { getCurrencySymbol } from '@razorpay/i18nify-js';
import { connect } from 'react-redux';

import { useSplitzService } from 'common/splitz';
import lazy from 'merchant/routes/LazyLoader';
import { useLinkedAccountBalance } from 'merchant/views/Settlements/InstantSettlements/query-hooks/useLinkedAccountBalance';
import { useODSConfig } from 'merchant/views/Settlements/InstantSettlements/query-hooks/useODSConfig';
import { useODSRestrictedConfig } from 'merchant/views/Settlements/InstantSettlements/query-hooks/useODSRestrictedConfig';
import { useOdsMutation } from 'merchant/views/Settlements/InstantSettlements/query-hooks/useOdsMutation';
import { usePGBalance } from 'merchant/views/Settlements/InstantSettlements/query-hooks/usePGBalance';
import { usePricingBreakup } from 'merchant/views/Settlements/InstantSettlements/query-hooks/usePricingBreakup';
import {
  trackRender,
  trackButton,
  trackField,
} from 'merchant/views/Settlements/InstantSettlements/utils/analytics';
import {
  getHasMerchantLevelLimit,
  getIsPartialOndemandSettlementEnabled,
  getIsRouteOndemandSettlementEnabled,
} from 'merchant/views/Settlements/InstantSettlements/utils/common';
import { ODSRestrictedBanner } from 'merchant/views/Settlements/Settlements/components/Modals/OnDemandV2/ODSRestrictedBanner';
import { useODSAutomaticPricingDiscount } from 'merchant/views/Settlements/InstantSettlements/hooks/useODSAutomaticPricingDiscount';
import {
  MODAL_PADDING,
  MODAL_HEADER_BG,
  formatAmount,
  SETTLEMENT_TYPES,
  type SettlementTypes,
  convertToMinorUnit,
  convertToMajorUnit,
  SCREENS,
  midLimitGTMViewedStatus,
} from 'merchant/views/Settlements/Settlements/components/Modals/OnDemandV2/helpers';
import { zIndicesMap } from 'common/constant';

const GtmModalContent = lazy(
  () =>
    import(
      /* webpackChunkName: "GtmModalContent", webpackPrefetch: true */
      'merchant/views/Settlements/Settlements/components/Modals/OnDemandV2/GtmModalContent'
    ),
);

const TOOLTIP_CONTENT = {
  PG_BALANCE: 'This is the live payment gateway balance that can be withdrawn instantly.',
  MID_LIMIT: {
    LIMIT: 'This is the maximum amount that you can withdraw per day.',
    AVAILABLE:
      'Remaining daily limit is calculated as Maximum daily withdrawal limit minus Amount withdrawn today. Remaining daily limit will be refreshed the next working day.',
  },
};

const MAX_AMOUNT = 2000000000; // In Paise
const MIN_AMOUNT = 10000; // In Paise

const KeyValuePair = ({
  title,
  value,
  currency,
  isBold,
  tooltip,
}: {
  title: string;
  /** In Paise */
  value: number;
  currency: 'INR';
  isBold?: boolean;
  tooltip?: { content: string; type: string };
}) => {
  const fontWeight = isBold ? 'semibold' : undefined;

  const onTooltipOpen = ({ isOpen }: { isOpen: boolean }) => {
    if (isOpen) {
      trackRender({
        screen: SCREENS.WITHDRAW,
        context: `tooltip-${tooltip!.type}`,
      });
    }
  };

  return (
    <Box display="flex" justifyContent="space-between" gap="spacing.4">
      <Box display="flex">
        <Text color="surface.text.gray.subtle" weight={fontWeight}>
          {title}
        </Text>
        {tooltip ? (
          <Tooltip
            placement="bottom"
            onOpenChange={onTooltipOpen}
            zIndex={zIndicesMap.tooltip}
            content={tooltip.content}
          >
            <Box marginLeft="spacing.2" display="inline-flex" alignItems="center">
              <HelpCircleIcon color="surface.icon.gray.muted" size="small" />
            </Box>
          </Tooltip>
        ) : null}
      </Box>
      <Amount
        value={convertToMajorUnit(value, { currency })}
        currency={currency}
        weight={fontWeight}
      />
    </Box>
  );
};

const ConfirmScreen = ({
  isOpen,
  amount,
  type,
  user,
  currency,
  onDismiss,
  onSuccess,
}: {
  isOpen: boolean;
  currency: 'INR';
  amount: number;
  user: any;
  type: SettlementTypes;
  onDismiss: VoidFunction;
  onSuccess: VoidFunction;
}) => {
  const isRouteOds = type === SETTLEMENT_TYPES.ROUTE;

  const odsMutation = useOdsMutation();
  const isLoading = odsMutation.isLoading || odsMutation.isPaused;

  const onSubmit = () => {
    odsMutation.mutate(
      isRouteOds ? { type, merchantId: user.merchant.id, amount } : { type, amount, currency },
      {
        onSuccess: () => {
          onSuccess();
        },
        onError: () => {
          trackRender({
            screen: SCREENS.WITHDRAW,
            context: 'confirm-error',
          });
        },
      },
    );
    trackButton({
      screen: SCREENS.WITHDRAW,
      name: 'Yes Settle',
    });
  };

  const onClose = () => {
    onDismiss();
    trackButton({
      name: 'Dont Settle',
      screen: SCREENS.WITHDRAW,
    });
  };

  return (
    <Modal isOpen={isOpen} onDismiss={() => {}}>
      <ModalBody padding="spacing.0">
        <Box padding={MODAL_PADDING}>
          <Box display="flex" alignItems="center" justifyContent="space-between" gap="spacing.4">
            <Heading size="small">Confirm Settlement</Heading>
            <IconButton
              size="large"
              icon={CloseIcon}
              accessibilityLabel="Close"
              onClick={onClose}
            />
          </Box>
          <Text marginTop="spacing.3" marginBottom="spacing.7" color="surface.text.gray.subtle">
            Are you sure you want to proceed with the settlement of{' '}
            <Amount
              color="surface.text.gray.subtle"
              suffix="none"
              value={convertToMajorUnit(amount, { currency })}
              currency={currency}
            />
            ?
          </Text>
          <Box textAlign="right">
            <Button
              isDisabled={isLoading}
              variant="tertiary"
              marginRight="spacing.5"
              onClick={onClose}
            >
              Don’t Settle
            </Button>
            <Button isLoading={isLoading} onClick={onSubmit}>
              Yes, Settle
            </Button>
          </Box>
        </Box>
      </ModalBody>
    </Modal>
  );
};

/** No Actions allowed */
const HardLoading = () => {
  return (
    <Box borderRadius="large" overflow="hidden">
      <ProgressBar isIndeterminate />
      <Box padding={MODAL_PADDING}>
        <Skeleton borderRadius="medium" height="22px" width="60%" />
        <Skeleton borderRadius="medium" marginTop="spacing.4" height="16px" width="80%" />
        <Skeleton borderRadius="medium" marginTop="60px" height="48px" />
        <Skeleton borderRadius="medium" marginTop="82px" height="36px" />
      </Box>
    </Box>
  );
};

/** Analytics utils */
let hasEditedAmount = false;

/**
 * Note: Linked account will be deprecated soon in-favour of alternative offered by settlements service
 * TODO: Deprecate old ODS modal code once new flow rolled out to all merchants via splitz
 * */
const WithdrawalScreen = ({
  defaultAmount,
  defaultType,
  currency,
  onNext,
  user,
}: {
  currency: 'INR';
  user: any;
  defaultAmount?: number;
  defaultType?: SettlementTypes;
  onNext: (data: { amount: number; type: SettlementTypes }) => void;
}) => {
  const isOndemandRouteSettlementsEnabled = getIsRouteOndemandSettlementEnabled(user);
  const isODSRestricted = getIsPartialOndemandSettlementEnabled(user);
  /** For linked account, must use linkedAccountBalance */
  const [amount, setAmount] = useState(() => {
    /** defaultAmount is used when navigating back to this screen (valid only for SETTLEMENT_TYPES.ODS) */
    return defaultType === SETTLEMENT_TYPES.ODS && defaultAmount
      ? convertToMajorUnit(defaultAmount, { currency, keepDecimal: false })
      : 0;
  });
  const [shouldShowPricingBreakup, setShouldShowPricingBreakup] = useState(false);
  const [shouldShowConfirm, setShouldShowConfirm] = useState(false);
  /** isInputDirty - causes validation messages to start appearing */
  const [isInputDirty, setIsInputDirty] = useState(false);
  const [selectedTab, setSelectedTab] = useState<SettlementTypes>(
    defaultType === SETTLEMENT_TYPES.ROUTE && isOndemandRouteSettlementsEnabled
      ? SETTLEMENT_TYPES.ROUTE
      : SETTLEMENT_TYPES.ODS,
  );

  const amountInPaise = convertToMinorUnit(amount, { currency });
  const isLinkedAccountTabActive = selectedTab === SETTLEMENT_TYPES.ROUTE;

  const pgBalanceQuery = usePGBalance();
  const odsConfigQuery = useODSConfig();
  const odsRestrictedConfigQuery = useODSRestrictedConfig({
    enabled: isODSRestricted,
  });
  const linkedAccountBalanceQuery = useLinkedAccountBalance({ enabled: isLinkedAccountTabActive });

  const hasMIDLevelLimit = getHasMerchantLevelLimit(odsConfigQuery.data?.available_limit);
  /** Avoid fallback value of zero to handle cases where value not present - backward compactibility */
  const dailyMaxLimit = isODSRestricted
    ? odsRestrictedConfigQuery.data?.max_amount_limit
    : odsConfigQuery.data?.max_limit;
  /** Avoid fallback value of zero to handle cases where value not present - backward compactibility */
  const dailyAvailableLimit = isODSRestricted
    ? odsRestrictedConfigQuery.data?.settlable_amount
    : odsConfigQuery.data?.available_limit;
  const showDailyLimit = isODSRestricted || hasMIDLevelLimit;
  const currentBalance = pgBalanceQuery.data?.balance || 0;
  const linkedAccountBalance = Number(linkedAccountBalanceQuery.data?.balance) || 0;

  const errorMessage = ((): string => {
    // Linked Account
    if (isLinkedAccountTabActive) {
      if (linkedAccountBalanceQuery.isError) {
        return 'Unable to retrieve balance. Please try again later.';
      }
      return !linkedAccountBalance
        ? 'Your linked accounts don’t have any pending settlements.'
        : '';
    }
    // Normal ODS
    if (amountInPaise < MIN_AMOUNT) {
      return `Minimum settlement amount should be ${formatAmount(MIN_AMOUNT, currency)}`;
    }
    // let BE handle cases where amount is zero. ideally settle now CTA should be disabled
    if (currentBalance > 0 && amountInPaise > currentBalance) {
      return `Maximum settlement amount is ${formatAmount(currentBalance, currency)}`;
    }
    if ((dailyAvailableLimit || 0) > 0 && amountInPaise > (dailyAvailableLimit || 0)) {
      return `Maximum settlement amount is ${formatAmount(dailyAvailableLimit || 0, currency)}`;
    }
    return '';
  })();

  const pricingBreakupQuery = usePricingBreakup({
    amount: amountInPaise,
    currency,
    enabled: !errorMessage && !isLinkedAccountTabActive,
  });
  /** Prefetching data - useODSAutomaticPricingDiscount */
  useODSAutomaticPricingDiscount(currency);
  const isPricingLoading = pricingBreakupQuery.isInitialLoading;
  const pricingPercent = (pricingBreakupQuery.data?.items[0].pricing_rule.percent_rate || 0) / 100;
  const pricingFee = pricingBreakupQuery.data?.items[0].amount || 0;
  const taxFee = pricingBreakupQuery.data?.items[1].amount || 0;
  const shouldDisableShowBreakup =
    isPricingLoading || pricingBreakupQuery.isError || !!errorMessage;

  const [hasSeenGTMModal, setHasSeenGTMModal] = useState(() => {
    return midLimitGTMViewedStatus.isViewed();
  });
  const {
    abExperiments: { capital_is_gtm },
  } = useSplitzService();
  const isMIDLimitGTMExpActive = capital_is_gtm?.variables?.result === 'on';
  const shouldShowMIDGtm =
    !isODSRestricted && hasMIDLevelLimit && isMIDLimitGTMExpActive && !hasSeenGTMModal;

  const isFetchingInitialData =
    pgBalanceQuery.isInitialLoading ||
    odsConfigQuery.isInitialLoading ||
    (isODSRestricted && odsRestrictedConfigQuery.isInitialLoading);
  /** Using isFetchingInitialData as state initialiser, allows us to avoid CLS when going to next screen(confirm screen) and coming back here*/
  const [isInitialised, setIsInitialised] = useState(!isFetchingInitialData);

  const shouldDisableConfirmCta = isLinkedAccountTabActive
    ? linkedAccountBalanceQuery.isFetching
    : false;

  const settleNowPayload = isLinkedAccountTabActive
    ? {
        amount: linkedAccountBalance,
        type: SETTLEMENT_TYPES.ROUTE,
      }
    : { amount: amountInPaise, type: SETTLEMENT_TYPES.ODS };

  useEffect(() => {
    /** Explains "if" condition -> no cache || with cache */
    if ((!isFetchingInitialData && !isInitialised) || (isInitialised && !amount)) {
      setIsInitialised(true);
      if (!amount) {
        setAmount(
          convertToMajorUnit(
            isODSRestricted || hasMIDLevelLimit
              ? Math.min(dailyAvailableLimit || 0, currentBalance, MAX_AMOUNT)
              : Math.min(currentBalance, MAX_AMOUNT),
            {
              currency,
              keepDecimal: false,
            },
          ),
        );
      }
    }
  }, [isFetchingInitialData, isInitialised]);

  useEffect(() => {
    if (shouldDisableShowBreakup && shouldShowPricingBreakup && !isPricingLoading) {
      setShouldShowPricingBreakup(false);
    }
  }, [shouldDisableShowBreakup]);

  const onAmountChange = ({ value }: { value?: string }): void => {
    value = (value || '').replace(/[^0-9]+/g, '');
    setAmount(Number(value) || 0);
    setIsInputDirty(true);
    if (!hasEditedAmount) {
      hasEditedAmount = true;
      trackField({ name: 'amount', screen: SCREENS.WITHDRAW });
    }
  };

  const onTabChange = (tab: string) => {
    setSelectedTab(tab as SettlementTypes);
    setIsInputDirty(false);
    trackButton({
      name: 'tab',
      screen: SCREENS.WITHDRAW,
      properties: {
        tab_value: tab,
      },
    });
  };

  const onSubmit = () => {
    if (errorMessage) {
      setIsInputDirty(true);
      return;
    }
    setShouldShowConfirm(true);
    trackButton({
      name: 'Confirm Settlement',
      screen: SCREENS.WITHDRAW,
    });
  };

  const onSuccess = () => {
    onNext(settleNowPayload);
  };

  const onConfirmDismiss = () => {
    setShouldShowConfirm(false);
  };

  const onShowBreakup = () => {
    setShouldShowPricingBreakup(!shouldShowPricingBreakup);
    trackButton({
      screen: SCREENS.WITHDRAW,
      name: 'show-breakup',
    });
  };

  /** Normal ODS - SETTLEMENT_TYPES.ODS */
  const renderODSContent = () => {
    return (
      <>
        {/* Limit-info */}
        <Box padding={MODAL_PADDING} backgroundColor={MODAL_HEADER_BG}>
          <KeyValuePair
            title="Current balance"
            tooltip={{
              content: TOOLTIP_CONTENT.PG_BALANCE,
              type: 'pg',
            }}
            value={currentBalance}
            currency={currency}
          />
          {/* MID level limit or IS Restricted limit */}
          {showDailyLimit ? (
            !isODSRestricted ? (
              <>
                <Divider variant="normal" width="40px" marginY="spacing.6" />
                <KeyValuePair
                  title="Maximum daily withdrawal limit"
                  tooltip={{
                    content: TOOLTIP_CONTENT.MID_LIMIT.LIMIT,
                    type: 'max',
                  }}
                  value={dailyMaxLimit || 0}
                  currency={currency}
                />
                <Box marginTop="spacing.3">
                  <KeyValuePair
                    title="Remaining daily limit"
                    tooltip={{
                      content: TOOLTIP_CONTENT.MID_LIMIT.AVAILABLE,
                      type: 'avail',
                    }}
                    value={dailyAvailableLimit || 0}
                    currency={currency}
                  />
                </Box>
              </>
            ) : (
              <ODSRestrictedBanner currency={currency} maxLimit={dailyMaxLimit || 0} />
            )
          ) : null}
        </Box>
        {/* Body */}
        <Box padding={MODAL_PADDING}>
          <Text weight="semibold">How much do you want to settle now?</Text>
          <TextInput
            value={amount ? String(amount) : ''}
            marginTop="spacing.3"
            marginBottom="spacing.11"
            prefix={getCurrencySymbol(currency)}
            size="large"
            accessibilityLabel="How much do you want to settle now?"
            validationState={errorMessage && isInputDirty ? 'error' : 'none'}
            errorText={errorMessage}
            onChange={onAmountChange}
          />
          {isPricingLoading ? <ProgressBar isIndeterminate /> : null}
          <Box paddingX="spacing.6" paddingY="spacing.4" backgroundColor={MODAL_HEADER_BG}>
            <Box display="flex" justifyContent="space-between" alignItems="center">
              <Text size="small" weight="semibold" color="surface.text.gray.subtle">
                {pricingPercent ? `${pricingPercent}%` : null} fees
              </Text>
              <Link
                variant="button"
                iconPosition="right"
                color={pricingBreakupQuery.isError ? 'negative' : undefined}
                isDisabled={shouldDisableShowBreakup}
                icon={shouldShowPricingBreakup ? ChevronUpIcon : ChevronDownIcon}
                onClick={onShowBreakup}
              >
                Show breakup
              </Link>
            </Box>
            <Collapsible isExpanded={shouldShowPricingBreakup}>
              <CollapsibleBody width="100%">
                <Divider />
                <Box marginTop="spacing.5" display="flex" flexDirection="column" gap="spacing.4">
                  <KeyValuePair
                    title="Settlement amount"
                    value={amountInPaise}
                    currency={currency}
                  />
                  <KeyValuePair
                    title={`Fees ${pricingPercent ? `(${pricingPercent}%)` : null}`}
                    value={pricingFee}
                    currency={currency}
                  />
                  <KeyValuePair title="GST" value={taxFee} currency={currency} />
                  <KeyValuePair
                    isBold
                    title="Amount to be settled"
                    value={amountInPaise - pricingFee - taxFee}
                    currency={currency}
                  />
                </Box>
              </CollapsibleBody>
            </Collapsible>
          </Box>
        </Box>
      </>
    );
  };
  /** Route ODS - SETTLEMENT_TYPES.ROUTE */
  const renderRouteContent = () => {
    return (
      <Box padding={MODAL_PADDING}>
        <Text weight="semibold">Amount pending to be settled</Text>
        <TextInput
          value={formatAmount(linkedAccountBalance, currency, true, false)}
          marginTop="spacing.3"
          marginBottom="spacing.11"
          prefix={getCurrencySymbol(currency)}
          size="large"
          accessibilityLabel="Amount pending to be settled?"
          validationState={errorMessage && isInputDirty ? 'error' : 'none'}
          helpText="The amount will be setlled to your linked accounts"
          errorText={errorMessage}
          isDisabled
          isLoading={linkedAccountBalanceQuery.isFetching}
        />
      </Box>
    );
  };

  if (isFetchingInitialData || !isInitialised) {
    return <HardLoading />;
  }

  if (shouldShowMIDGtm) {
    return (
      <Suspense fallback={<HardLoading />}>
        <GtmModalContent
          maxLimit={dailyMaxLimit || 0}
          currency={currency}
          onComplete={() => setHasSeenGTMModal(true)}
        />
      </Suspense>
    );
  }

  return (
    <Box borderRadius="large" overflow="hidden">
      {/* Header */}
      <Box
        paddingX={MODAL_PADDING}
        paddingTop={MODAL_PADDING}
        backgroundColor={!isOndemandRouteSettlementsEnabled ? MODAL_HEADER_BG : undefined}
      >
        <Text size="large" weight="semibold">
          Instant Settlements
        </Text>
        <Text color="surface.text.gray.muted" marginTop="spacing.1" size="small">
          Settle to your bank account instantly, even on holidays!
        </Text>
      </Box>
      {/* Body */}
      {isOndemandRouteSettlementsEnabled ? (
        <Tabs isFullWidthTabItem defaultValue={selectedTab} onChange={onTabChange}>
          <TabList marginTop="spacing.4">
            <TabItem value={SETTLEMENT_TYPES.ODS}>Settle to your account</TabItem>
            <TabItem value={SETTLEMENT_TYPES.ROUTE}>Settle to linked account</TabItem>
          </TabList>
          <TabPanel value={SETTLEMENT_TYPES.ODS}>{renderODSContent()}</TabPanel>
          <TabPanel value={SETTLEMENT_TYPES.ROUTE}>{renderRouteContent()}</TabPanel>
        </Tabs>
      ) : (
        renderODSContent()
      )}
      {/* Footer */}
      <Box marginX={MODAL_PADDING} marginBottom={MODAL_PADDING}>
        <Button isFullWidth isDisabled={shouldDisableConfirmCta} onClick={onSubmit}>
          Confirm Settlement
        </Button>
      </Box>
      {/* Common Modals */}
      <ConfirmScreen
        isOpen={shouldShowConfirm}
        user={user}
        currency={currency}
        amount={settleNowPayload.amount}
        type={settleNowPayload.type}
        onDismiss={onConfirmDismiss}
        onSuccess={onSuccess}
      />
    </Box>
  );
};

const mapStateToProps = (state) => {
  return {
    user: state.session.user,
  };
};

const _ReduxConnect = connect(mapStateToProps)(WithdrawalScreen);

export { _ReduxConnect as WithdrawalScreen };
