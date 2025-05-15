import { TreeSelect } from '@dashboards/payments/components/TreeSelect';
import {
  Box,
  Button,
  Chip,
  ChipGroup,
  FilterIcon,
  Modal,
  ModalBody,
  ModalFooter,
  ModalHeader,
} from '@razorpay/blade/components';
import React, { useState } from 'react';

import { renderInput } from 'merchant/widgets/common/utils';
import { AllFiltersModalProps } from './types';
import { ChannelIconMap, ChannelSourceMap, TransformToTreeData } from './utils';

const AllFiltersModal: React.FC<AllFiltersModalProps> = ({
  isOpen,
  onClose,
  input,
  date,
  variables,
  screen,
  widgetId,
  title,
  isMobile,
  isDatePickerEnabled,
  paymentSource,
  storeIds,
  componentWidget,
  modalTitle,
  onFilterApply,
  handleDateChange,
}) => {
  const [storeId, setStoreId] = useState<string[]>([]);
  const [sourceChannel, setSourceChannel] = useState<string>('');

  const handleApply = () => {
    onFilterApply({ stores: storeId, source: sourceChannel });
    onClose();
  };

  return (
    <Modal isOpen={isOpen} onDismiss={onClose} accessibilityLabel={modalTitle} size="small">
      <ModalHeader title={modalTitle} leading={<FilterIcon />} />
      <ModalBody>
        <>
          <Box display="flex" marginBottom={{ base: 'spacing.4', m: 'spacing.0' }}>
            {input &&
              isMobile &&
              renderInput({
                widget: { ...input, label: 'Time Period' },
                value: date,
                variables,
                onChange: handleDateChange,
                analyticsProperties: {
                  screen,
                  widgetId,
                  actionBy: widgetId,
                  title,
                },
                customRange: isDatePickerEnabled,
              })}
          </Box>
          {componentWidget?.map((component) => (
            <Box key={component.id} marginBottom="spacing.4">
              {component.type === 'payment_source_filter' && (
                <Box display="flex" gap="spacing.3">
                  <ChipGroup
                    label={component.title}
                    selectionType="single"
                    value={sourceChannel || component.inputs?.[0]?.default_value}
                    onChange={({ values }): void => {
                      setSourceChannel(values[0]);
                    }}
                  >
                    {component.inputs?.[0]?.values?.map((option) => (
                      <Chip key={option} value={option} icon={ChannelIconMap[option]}>
                        {ChannelSourceMap[option]}
                      </Chip>
                    ))}
                  </ChipGroup>
                </Box>
              )}
              {component.type === 'hierarchy_level' && (
                <Box>
                  <TreeSelect
                    value={storeId}
                    treeData={TransformToTreeData(
                      component.data?.merchant_store_hierarchy?.storeHierarchy,
                    )}
                    label={component.title}
                    labelSize="small"
                    labelColor="surface.text.gray.muted"
                    onChange={(selectedValues) => {
                      setStoreId(selectedValues as string[]);
                    }}
                  />
                </Box>
              )}
            </Box>
          ))}
        </>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
          <Button
            variant="tertiary"
            onClick={() => {
              setStoreId(storeIds);
              setSourceChannel(paymentSource);
              onClose();
            }}
          >
            Cancel
          </Button>
          <Button onClick={handleApply}>Apply</Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};

export default AllFiltersModal;
