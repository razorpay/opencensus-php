import React, { lazy, Suspense, useMemo } from 'react';
import {
  Box,
  Button,
  Modal,
  ModalBody,
  ModalFooter,
  ModalHeader,
  ActionList,
  ActionListItem,
  ActionListItemIcon,
  CheckIcon,
  ChevronRightIcon,
  Skeleton,
} from '@razorpay/blade/components';

import { ACTION_LIST, STEPS } from './constants';
import { useModal } from './states';
import { ActivationModalProps } from './types';

const IecCode = lazy(
  () => import(/* webpackChunkName: 'IntlBankTransferActivationIecCode' */ './IecCode'),
);
const InternationalDetails = lazy(
  () =>
    import(
      /* webpackChunkName: 'IntlBankTransferActivationInternationalDetails' */ './InternationalDetails'
    ),
);

const PurposeCode = lazy(
  () => import(/* webpackChunkName: 'IntlBankTransferActivationPurposeCode' */ './PurposeCode'),
);

const VideoKYC = lazy(
  () => import(/* webpackChunkName: 'IntlBankTransferActivationVideoKYC' */ './VideoKYC'),
);

const ActivationModal = ({ isOpen, onDismiss, ...props }: ActivationModalProps) => {
  const {
    steps,
    current,
    hiddenSteps,
    isSavingForm,
    currentStepSubmitBtnText,
    handleContinueClick,
  } = useModal({
    ...props,
    onDismiss,
  });

  const actionList = useMemo(() => {
    return ACTION_LIST.map(({ value, label }) => {
      if (hiddenSteps.has(value)) {
        return null;
      }

      return (
        <ActionListItem
          key={value}
          title={label}
          value={value.toString()}
          trailing={
            steps[value].isCompleted ? (
              <ActionListItemIcon icon={current === value ? ChevronRightIcon : CheckIcon} />
            ) : current === value ? (
              <ActionListItemIcon icon={ChevronRightIcon} />
            ) : null
          }
          isSelected={current === value}
        />
      );
    }).filter(Boolean);
  }, [current, hiddenSteps, steps]);

  return (
    <Modal isOpen={isOpen} onDismiss={onDismiss} size="large">
      <ModalHeader title="Activate international bank transfers" />
      <ModalBody padding="spacing.0">
        <Box display="flex">
          <Box
            backgroundColor="surface.background.gray.moderate"
            minHeight={{
              base: 'auto',
              l: '300px',
            }}
            overflowY="auto"
            flex={{
              base: '0 0 100%',
              l: '0 0 270px',
            }}
            padding={{
              base: 'spacing.4',
              l: 'spacing.6',
            }}
            borderWidth="thin"
            borderColor="surface.border.gray.muted"
            borderRadius="medium"
          >
            <ActionList>{actionList}</ActionList>
          </Box>
          <Box
            minHeight={{
              base: 'auto',
              l: '300px',
            }}
            overflowY="auto"
            flex={1}
            padding={{
              base: 'spacing.4',
              l: 'spacing.8',
            }}
          >
            <Suspense
              fallback={
                <Box>
                  <Skeleton width="120px" height="30px" marginBottom="spacing.5" />
                  <Skeleton width="100%" height="80px" />
                </Box>
              }
            >
              {current === STEPS.PURPOSE_CODE && <PurposeCode />}
              {current === STEPS.IEC_CODE && <IecCode />}
              {current === STEPS.INTERNATIONAL_DETAILS && <InternationalDetails />}
              {current === STEPS.VIDEO_KYC && <VideoKYC />}
            </Suspense>
          </Box>
        </Box>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" justifyContent="flex-end">
          <Button onClick={handleContinueClick} isLoading={isSavingForm}>
            {currentStepSubmitBtnText}
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};

export default ActivationModal;
