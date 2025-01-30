import React, { useEffect, useState } from 'react';
import {
  BottomSheet,
  BottomSheetBody,
  BottomSheetFooter,
  BottomSheetHeader,
  Box,
  Button,
  Card,
  CardBody,
  Checkbox,
  CheckboxGroup,
  Chip,
  ChipGroup,
  FilterIcon,
  Link,
  RefreshIcon,
  Text,
} from '@razorpay/blade/components';

import { MARKETPLACE_CATEGORIES } from 'merchant/views/RizeMarketplace/common/constants';

import { FilteringProps } from './types';

const DesktopFiltering = ({
  value: categories,
  onChange: setCategories,
}: FilteringProps): JSX.Element => {
  const isSomeCategorySelected = categories.length !== 0;
  const clearCategories = (): void => setCategories([]);

  return (
    <Box display={{ base: 'none', l: 'block' }} position="sticky" top="12rem" zIndex="10">
      <Box display="flex" alignItems="center" gap="spacing.5">
        <Text size="large" weight="semibold" color="surface.text.gray.muted">
          Categories
        </Text>

        <Link
          variant="button"
          icon={RefreshIcon}
          iconPosition="left"
          onClick={clearCategories}
          isDisabled={!isSomeCategorySelected}
        >
          Clear All
        </Link>
      </Box>
      <ChipGroup
        name="category"
        accessibilityLabel="Filter by category"
        selectionType="multiple"
        size="medium"
        display="grid"
        value={categories}
        marginTop="spacing.4"
        onChange={({ values }) => {
          setCategories(values);
        }}
      >
        <Box display="grid" gap="spacing.4">
          {MARKETPLACE_CATEGORIES.map(
            (category): JSX.Element => (
              <Chip key={category} value={category}>
                {category}
              </Chip>
            ),
          )}
        </Box>
      </ChipGroup>
    </Box>
  );
};

const MobileFiltering = ({
  value: categories,
  onChange: setCategories,
}: FilteringProps): JSX.Element => {
  const [isOpen, setIsOpen] = useState(false);
  const [localCategories, setLocalCategories] = useState<string[]>([]);

  useEffect(() => {
    setLocalCategories(categories);
  }, [isOpen, categories, setLocalCategories]);

  return (
    <Box
      borderTopWidth="thin"
      borderTopColor="surface.border.gray.muted"
      borderTopRightRadius="medium"
      borderTopLeftRadius="medium"
    >
      <Card>
        <CardBody>
          <Box display="flex" alignItems="center" justifyContent="center">
            <Button variant="tertiary" icon={FilterIcon} onClick={(): void => setIsOpen(true)}>
              Filters
            </Button>
          </Box>
        </CardBody>
      </Card>

      <BottomSheet
        isOpen={isOpen}
        onDismiss={(): void => setIsOpen(false)}
        snapPoints={[1.0, 1.0, 1.0]}
      >
        <BottomSheetHeader title="Filters" />

        <BottomSheetBody>
          <CheckboxGroup
            value={localCategories}
            onChange={({ values }): void => setLocalCategories(values)}
            size="medium"
          >
            <Box display="flex" flexDirection="column" gap="spacing.5">
              {MARKETPLACE_CATEGORIES.map(
                (category): JSX.Element => (
                  <Checkbox key={category} value={category}>
                    {category}
                  </Checkbox>
                ),
              )}
            </Box>
          </CheckboxGroup>
        </BottomSheetBody>

        <BottomSheetFooter>
          <Box display="flex" gap="spacing.5">
            <Button
              variant="secondary"
              icon={RefreshIcon}
              iconPosition="left"
              isFullWidth
              isDisabled={localCategories.length === 0}
              onClick={(): void => {
                setLocalCategories([]);
                setCategories([]);
              }}
            >
              Clear filters
            </Button>
            <Button
              isFullWidth
              onClick={(): void => {
                setCategories(localCategories);
                setIsOpen(false);
              }}
            >
              Apply filters
            </Button>
          </Box>
        </BottomSheetFooter>
      </BottomSheet>
    </Box>
  );
};

export { DesktopFiltering, MobileFiltering };
