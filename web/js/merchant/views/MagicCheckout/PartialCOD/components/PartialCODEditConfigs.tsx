import React, { Suspense, lazy, useContext, useState } from 'react';
import {
  Alert,
  Box,
  CashIcon,
  Heading,
  InfoIcon,
  Switch,
  Tooltip,
  TooltipInteractiveWrapper,
} from '@razorpay/blade/components';
import { PARTIAL_COD_TYPE } from 'merchant/views/MagicCheckout/PartialCOD/types';
import AdvancedSlabConfig from 'merchant/views/MagicCheckout/PartialCOD/components/AdvancedSlabConfig';
import BasicSlabConfig from 'merchant/views/MagicCheckout/PartialCOD/components/BasicSlabConfig';
import { PartialCODContext } from 'merchant/views/MagicCheckout/PartialCOD/context/PartialCODContext';

const TogglePartialCODConfirmModal = lazy(
  () =>
    import(
      /* webpackChunkName: 'MagicPartialCODTogglePartialCODConfirmModal' */ 'merchant/views/MagicCheckout/PartialCOD/components/TogglePartialCODConfirmModal'
    ),
);

const ShiprocketNoticeModal = lazy(
  () =>
    import(
      /* webpackChunkName: 'MagicPartialCODShiprocketNoticeModal' */ 'merchant/views/MagicCheckout/PartialCOD/components/ShiprocketNoticeModal'
    ),
);

const PartialCODEditConfigs = () => {
  const {
    configsLocal,
    isPartialCODEnabledLocal,
    setIsPartialCODEnabledLocal,
    handleUpdatePartialCODStatus,
  } = useContext(PartialCODContext);

  const [isToggleModalOpen, setIsToggleModalOpen] = useState(false);
  const [isShiprocketModalOpen, setIsShiprockeModalOpen] = useState(false);

  return (
    <>
      <Box
        alignItems="center"
        justifyContent="space-between"
        display="flex"
        width={{ base: '100%', l: '438px' }}
        marginBottom="12px"
      >
        <Box alignItems="center" display="flex" gap="spacing.4">
          <CashIcon size="xlarge" color="interactive.icon.primary.normal" />
          <Heading size="small">Enable Partial COD</Heading>
          <Tooltip
            content="Partial COD allows your customers to pay a small amount upfront, reducing cancellations while offering the convenience of cash on delivery for the remaining balance"
            placement="right"
          >
            <TooltipInteractiveWrapper>
              <Box display="flex" alignItems="center">
                <InfoIcon color="surface.icon.gray.muted" size="large" />
              </Box>
            </TooltipInteractiveWrapper>
          </Tooltip>
        </Box>
        <Switch
          isChecked={isPartialCODEnabledLocal}
          onChange={() => setIsToggleModalOpen(true)}
          accessibilityLabel="Toggle Partial COD"
          size="medium"
        />
      </Box>
      <Box width={{ base: '100%', l: '510px' }}>
        <Alert
          actions={{
            secondary: {
              onClick: () => setIsShiprockeModalOpen(true),
              target: '_blank',
              text: 'View',
            },
          }}
          color="notice"
          description="Important action, if you use Shiprocket and want to enable Partial COD"
          emphasis="subtle"
          onDismiss={function noRefCheck() {}}
          isDismissible={false}
          isFullWidth={true}
        />
      </Box>
      <div>
        <div>
          {configsLocal.type === PARTIAL_COD_TYPE.BASIC && <BasicSlabConfig />}
          {/* Category */}
          {configsLocal.type === PARTIAL_COD_TYPE.ADVANCED && <AdvancedSlabConfig />}
        </div>
      </div>
      <Suspense fallback={null}>
        <TogglePartialCODConfirmModal
          isOpen={isToggleModalOpen}
          onClose={() => setIsToggleModalOpen(false)}
          onConfirm={() => {
            handleUpdatePartialCODStatus(!isPartialCODEnabledLocal, {
              onSuccess: () => setIsPartialCODEnabledLocal(!isPartialCODEnabledLocal),
            });
          }}
          isEnabled={isPartialCODEnabledLocal}
        />
      </Suspense>
      <Suspense fallback={null}>
        <ShiprocketNoticeModal
          isOpen={isShiprocketModalOpen}
          onClose={() => setIsShiprockeModalOpen(false)}
        />
      </Suspense>
    </>
  );
};

export default PartialCODEditConfigs;
