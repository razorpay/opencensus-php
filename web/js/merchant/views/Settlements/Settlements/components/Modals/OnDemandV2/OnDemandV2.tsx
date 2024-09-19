import React, { useState, useEffect } from 'react';
import {
  Button,
  Box,
  Text,
  Amount,
  Collapsible,
  CollapsibleBody,
  IconButton,
  CloseIcon,
  Heading,
  Divider,
  RadioGroup,
  Radio,
  TextArea,
} from '@razorpay/blade/components';
import { connect } from 'react-redux';

import {
  trackRender,
  trackButton,
} from 'merchant/views/Settlements/InstantSettlements/utils/analytics';
import { getIsSamedaySettlementEnabled } from 'merchant/views/Settlements/InstantSettlements/utils/common';
import { WithdrawalScreen } from 'merchant/views/Settlements/Settlements/components/Modals/OnDemandV2/WithdrawalScreen';
import {
  MODAL_PADDING,
  SETTLEMENT_TYPES,
  type SettlementTypes,
  convertToMajorUnit,
  SCREENS,
} from 'merchant/views/Settlements/Settlements/components/Modals/OnDemandV2/helpers';
import Upselling from 'merchant/views/Settlements/Settlements/components/Modals/ScheduledModal/components/Upselling';
import { SAMEDAY_MODAL_LOCATIONS } from 'merchant/views/Settlements/Settlements/components/Modals/ScheduledModal/constants';
import { CLOSE_OPTIONS } from 'merchant/views/Settlements/Settlements/data.js';
import { closeModal } from 'merchant_common/reducers/modals';

const SuccessScreen = ({
  currency,
  amount,
  onClose,
  user,
}: {
  amount: number;
  currency: 'INR';
  onClose: VoidFunction;
  user: any;
}) => {
  const [shouldShowBanner, setShouldShowBanner] = useState(false);

  const isSamedaySettlementEnabled = getIsSamedaySettlementEnabled(user);

  useEffect(() => {
    let timeoutId: NodeJS.Timeout;
    if (!isSamedaySettlementEnabled && !shouldShowBanner) {
      timeoutId = setTimeout(() => {
        setShouldShowBanner(true);
        trackRender({
          screen: SCREENS.SUCCESS,
          context: 'same-day-banner',
        });
      }, 1200);
    }
    return () => {
      if (timeoutId) {
        clearTimeout(timeoutId);
      }
    };
  }, [isSamedaySettlementEnabled]);

  return (
    <>
      <Box
        paddingX={MODAL_PADDING}
        paddingTop={MODAL_PADDING}
        paddingBottom={isSamedaySettlementEnabled ? MODAL_PADDING : undefined}
      >
        <Heading marginTop="spacing.6" size="small">
          Settlement Initiated 🎉
        </Heading>
        <Text
          marginTop="spacing.3"
          marginBottom={isSamedaySettlementEnabled ? 'spacing.7' : 'spacing.8'}
          color="surface.text.gray.subtle"
        >
          Settlement of{' '}
          <Amount
            color="surface.text.gray.subtle"
            suffix="none"
            value={convertToMajorUnit(amount, { currency })}
            currency={currency}
          />{' '}
          has been initiated and should soon reflect in your bank account
        </Text>
        {isSamedaySettlementEnabled ? (
          <Button isFullWidth onClick={onClose}>
            Close
          </Button>
        ) : null}
      </Box>
      {!isSamedaySettlementEnabled ? (
        <Collapsible isExpanded={shouldShowBanner}>
          <CollapsibleBody _hasMargin={false}>
            <Upselling showDiscount from={SAMEDAY_MODAL_LOCATIONS.ONDEMAND_V2} />
          </CollapsibleBody>
        </Collapsible>
      ) : null}
    </>
  );
};

const CloseReason = ({ onBack, onClose }: { onBack: VoidFunction; onClose: VoidFunction }) => {
  const [selectedReason, setSelectedReason] = useState<string>('');
  const [customReason, setCustomReason] = useState<string>('');
  /** isInputDirty - causes validation messages to start appearing */
  const [isInputDirty, setIsInputDirty] = useState(false);
  const isOthersSelected = selectedReason === 'other';

  const errorMsg =
    !selectedReason || (isOthersSelected && !customReason)
      ? 'Your feedback helps us improve. Please provide your comments.'
      : '';

  const handleSubmit = () => {
    if (errorMsg) {
      setIsInputDirty(true);
      return;
    }
    onClose();
    trackButton({
      name: 'confirm-reason',
      screen: SCREENS.CANCEL_REASONS,
      properties: {
        reason: selectedReason,
        feedback: customReason,
      },
    });
  };

  return (
    <>
      {/* Header */}
      <Box padding={MODAL_PADDING}>
        <Text size="large" weight="semibold">
          Reason
        </Text>
      </Box>
      <Divider />
      {/* Body */}
      <Box padding={MODAL_PADDING}>
        <RadioGroup
          value={selectedReason}
          onChange={({ value }) => {
            setSelectedReason(value);
          }}
          errorText={errorMsg}
          validationState={errorMsg && !isOthersSelected && isInputDirty ? 'error' : 'none'}
        >
          {CLOSE_OPTIONS.map((reason) => {
            return (
              <Radio size="small" key={reason.value} value={reason.value}>
                {reason.label}
              </Radio>
            );
          })}
        </RadioGroup>
        {isOthersSelected ? (
          <TextArea
            autoFocus
            marginTop="spacing.2"
            accessibilityLabel="Write a brief description"
            placeholder="Write a brief description"
            errorText={errorMsg}
            validationState={errorMsg ? 'error' : 'none'}
            maxCharacters={100}
            onChange={({ value }) => {
              setCustomReason((value || '').trim());
            }}
          />
        ) : null}
      </Box>
      {/* Footer */}
      <Divider />
      <Box textAlign="right" padding={MODAL_PADDING}>
        <Button variant="tertiary" marginRight="spacing.5" onClick={onBack}>
          Go Back
        </Button>
        <Button onClick={handleSubmit}>Confirm & Close</Button>
      </Box>
    </>
  );
};

const OnDemandV2 = ({ user }: { user: any }) => {
  const [screen, setScreen] = useState<typeof SCREENS[keyof typeof SCREENS]>(SCREENS.WITHDRAW);
  const [settlementDetails, setSettlementDetails] = useState<{
    amount: number;
    type: SettlementTypes;
  }>({ amount: 0, type: SETTLEMENT_TYPES.ODS });

  const currency = (user.merchant.currency || 'INR') as 'INR';

  useEffect(() => {
    trackRender({
      screen,
    });
  }, [screen]);

  /** Triggerd after successful completion of current screen */
  const onNext = (data?: typeof settlementDetails) => {
    setTimeout(() => {
      trackButton({
        name: 'next',
        screen,
      });
    }, 50);
    if (screen === SCREENS.WITHDRAW) {
      setSettlementDetails(data as typeof settlementDetails);
      setScreen(SCREENS.SUCCESS);
      return;
    }

    if (screen === SCREENS.SUCCESS || screen === SCREENS.CANCEL_REASONS) {
      closeModal();
    }
  };

  const onBack = () => {
    if (screen === SCREENS.CANCEL_REASONS) {
      setScreen(SCREENS.WITHDRAW);
    }
    trackButton({
      name: 'back',
      screen,
    });
  };

  const onClose = () => {
    setTimeout(() => {
      trackButton({
        name: 'close',
        screen,
      });
    }, 50);
    if (screen === SCREENS.SUCCESS || screen === SCREENS.CANCEL_REASONS) {
      closeModal();
      return;
    }
    setScreen(SCREENS.CANCEL_REASONS);
  };

  const renderWithCloseBtn = (children: React.ReactNode) => {
    return (
      <Box position="relative">
        {children}
        <Box
          position="absolute"
          padding="spacing.2"
          borderRadius="round"
          backgroundColor="surface.background.gray.intense"
          top={MODAL_PADDING}
          right={MODAL_PADDING}
        >
          <IconButton size="large" icon={CloseIcon} accessibilityLabel="Close" onClick={onClose} />
        </Box>
      </Box>
    );
  };

  return (
    <>
      {/* Render respective screen with custom layout per screen */}
      {screen === SCREENS.WITHDRAW
        ? renderWithCloseBtn(
            <WithdrawalScreen
              defaultAmount={settlementDetails.amount}
              defaultType={settlementDetails.type}
              currency={currency}
              onNext={onNext}
            />,
          )
        : null}

      {screen === SCREENS.SUCCESS
        ? renderWithCloseBtn(
            <SuccessScreen
              amount={settlementDetails.amount}
              currency={currency}
              user={user}
              onClose={onClose}
            />,
          )
        : null}

      {screen === SCREENS.CANCEL_REASONS ? <CloseReason onBack={onBack} onClose={onClose} /> : null}
    </>
  );
};

const mapStateToProps = (state) => {
  return {
    user: state.session.user,
  };
};

const _ReduxConnect = connect(mapStateToProps)(OnDemandV2);

export { _ReduxConnect as OnDemandV2 };

export default _ReduxConnect;
