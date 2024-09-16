import React, { useEffect, useState } from 'react';
import {
  FilterIcon,
  Modal,
  ModalBody,
  ModalHeader,
  Box,
  ChipGroup,
  Chip,
  Text,
  Divider,
  ModalFooter,
  Button,
} from '@razorpay/blade/components';

import { disputeFilters } from 'merchant/views/Transactions/v2/Disputes/constants';
import { SelectedFilterType } from 'merchant/views/Transactions/v2/Disputes/types';

interface DisputeFilterProps {
  isOpen: boolean;
  selectedFilters: SelectedFilterType;
  onDismiss: () => void;
  onFilterApply: (filters: SelectedFilterType) => void;
}

const DisputeFilter = ({
  isOpen,
  onDismiss,
  onFilterApply,
  selectedFilters: previouslySelectedFilters,
}: DisputeFilterProps): JSX.Element => {
  const [selectedFilters, setSelectedFilters] = useState<SelectedFilterType>({});

  useEffect(() => {
    setSelectedFilters(previouslySelectedFilters);
  }, [previouslySelectedFilters]);

  const handleSelectedFilters = ({ name, values }: { name: string; values: string[] }) => {
    setSelectedFilters((prev) => ({ ...prev, [name]: values }));
  };

  const handleDismiss = () => {
    onDismiss();
    setSelectedFilters(previouslySelectedFilters);
  };

  return (
    <Modal isOpen={isOpen} onDismiss={handleDismiss}>
      <ModalHeader title="Filter" leading={<FilterIcon />} />
      <ModalBody>
        {Object.entries(disputeFilters).map((disputeFilter, index) => {
          const [title, filtersList] = disputeFilter;
          return (
            <Box key={`disputeFilterTitle-${index}`}>
              <Box paddingBottom="spacing.7" paddingTop={index === 0 ? 'spacing.0' : 'spacing.6'}>
                <Text color="surface.text.gray.normal" weight="semibold" size="medium">
                  {title}
                </Text>
                <Box paddingTop="spacing.3">
                  <ChipGroup
                    name={title}
                    value={selectedFilters?.[title]}
                    selectionType="multiple"
                    accessibilityLabel="Choose filter"
                    onChange={({ name, values }) => handleSelectedFilters({ name, values })}
                  >
                    {filtersList.map((filter) => {
                      return (
                        <Chip value={filter} key={filter}>
                          {filter}
                        </Chip>
                      );
                    })}
                  </ChipGroup>
                </Box>
              </Box>
              {index !== Object.entries(disputeFilters).length - 1 ? <Divider /> : null}
            </Box>
          );
        })}
      </ModalBody>
      <ModalFooter>
        <Box display="flex" alignItems="center" justifyContent="end" columnGap="spacing.5">
          <Button variant="secondary" onClick={handleDismiss}>
            Cancel
          </Button>
          <Button onClick={() => onFilterApply(selectedFilters)}>Apply</Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};

export default DisputeFilter;
