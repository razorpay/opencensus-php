import React, { useState } from 'react';
import { connect } from 'react-redux';
import { closeModal } from 'merchant_common/reducers/modals';
import { bindActionCreators } from 'redux';
import {
  Button,
  Box,
  ModalHeader as BladeModalHeader,
  ModalBody as BladeModalBody,
  ModalFooter as BladeModalFooter,
  Modal as BladeModal,
  Text,
  Dropdown,
  AutoComplete,
  DropdownOverlay,
  ActionList,
  ActionListItem,
  DropdownFooter,
  ActionListSection,
  BottomSheet,
  BottomSheetHeader,
  BottomSheetBody,
  BottomSheetFooter,
  RadioGroup,
  Radio,
} from '@razorpay/blade/components';
import { useMobile } from 'common/hooks/useMobile';
import HelpContent from './HelpContent';
import {
  AlertType,
  UpdateModalProps,
} from 'merchant/views/AccountAndSettings/BusinessSettings/Tabs/GSTDetails/types';
import { fetchGST } from 'merchant/reducers/profile';
import { track } from 'merchant/views/AccountAndSettings/BusinessSettings/Tabs/GSTDetails/tracking';
import { submitGSTDetails } from 'merchant/views/AccountAndSettings/BusinessSettings/Tabs/GSTDetails/model';

const defaultSnapPoints: [number, number, number] = [0.5, 0.7, 0.85];

const UpdateModal = ({
  fetchGST,
  closeModal,
  gstList,
  defaultGSTIn,
  setAlertStatus,
}: UpdateModalProps): JSX.Element => {
  const isMobile = useMobile();
  const [selectedGSTIn, setSelectedGSTIn] = useState<string>();
  const [isOpen, setIsOpen] = useState<boolean>(true);
  const [isLoading, setIsLoading] = useState<boolean>(false);
  const { Modal, ModalHeader, ModalBody, ModalFooter } = isMobile
    ? {
        Modal: BottomSheet,
        ModalHeader: BottomSheetHeader,
        ModalBody: BottomSheetBody,
        ModalFooter: BottomSheetFooter,
      }
    : {
        Modal: BladeModal,
        ModalHeader: BladeModalHeader,
        ModalBody: BladeModalBody,
        ModalFooter: BladeModalFooter,
      };

  const commonProps = {
    necessityIndicator: 'required',
    isRequired: true,
    helpText: 'You can only choose GST numbers linked to your PAN card',
    label: 'Select GST number',
    defaultValue: defaultGSTIn,
    value: selectedGSTIn || defaultGSTIn,
  } as const;

  const dismissModal = () => {
    if (isMobile) {
      // unset overflow when using Blade BottomSheet on mobile as it doesn't happen automatically
      // when used with ModalDialog. If overflow is not set back to default then page stops scrolling.
      document.body.style.overflow = 'unset';
    }
    setIsOpen(false);
    closeModal();
  };

  const handleSubmit = async (): Promise<void> => {
    setIsLoading(true);
    track({
      objectName: 'Update GST Submit Button',
      properties: {
        GSTNumberSelected: selectedGSTIn,
      },
    });
    const formData = new FormData();
    formData.append('gstin', selectedGSTIn as string);
    formData.append('version', 'v2');
    try {
      const response = await submitGSTDetails({ formData });
      if (response?.data?.sync_flow) {
        fetchGST();
        setAlertStatus({ type: AlertType.SUCCESS, gstIN: response.data.gstin });
      } else {
        throw Error();
      }
    } catch {
      setAlertStatus({ type: AlertType.FAILURE });
    } finally {
      setIsLoading(false);
      dismissModal();
    }
  };

  return (
    // zIndex is required to render the Modal on top of the left sidebar with z-index as 1111
    <Modal zIndex={1112} isOpen={isOpen} onDismiss={dismissModal} snapPoints={defaultSnapPoints}>
      <ModalHeader title="Edit GST detail" />
      <ModalBody>
        <Box display="flex" flexDirection="column" gap="spacing.7">
          {isMobile ? (
            <RadioGroup {...commonProps} onChange={({ value }) => setSelectedGSTIn(value)}>
              {gstList.map((gstIn) => (
                <Radio key={gstIn} value={gstIn}>
                  {gstIn}
                </Radio>
              ))}
            </RadioGroup>
          ) : (
            <Dropdown selectionType="single">
              <AutoComplete
                {...commonProps}
                onChange={({ values }) => setSelectedGSTIn(values[0])}
              />
              <DropdownOverlay>
                {/* Ignoring TS check for Blade ActionList as its chilren type is not correctly defined to
                accept a single child */}
                {/* @ts-ignore:next-line */}
                <ActionList>
                  <ActionListSection title="GST numbers linked with your PAN card">
                    {gstList.map((gstIn) => (
                      <ActionListItem key={gstIn} title={gstIn} value={gstIn} />
                    ))}
                  </ActionListSection>
                </ActionList>
                <DropdownFooter>
                  <HelpContent />
                </DropdownFooter>
              </DropdownOverlay>
            </Dropdown>
          )}
          {isMobile ? <HelpContent /> : null}
          <Text>Your Business Address will also be updated to your GSTIN Registered Address</Text>
        </Box>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" justifyContent="flex-end" width="100%">
          <Button
            isLoading={isLoading}
            isFullWidth={isMobile}
            isDisabled={!selectedGSTIn}
            onClick={handleSubmit}
          >
            Submit
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      fetchGST,
      closeModal,
    },
    dispatch,
  );

export default connect(null, mapDispatchToProps)(UpdateModal);
