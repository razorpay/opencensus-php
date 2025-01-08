import React from 'react';
import {
  Box,
  Heading,
  Text,
  Link,
  TextInput,
  Dropdown,
  DropdownOverlay,
  Radio,
  RadioGroup,
  SearchIcon,
  SelectInput,
  ActionList,
  ActionListItem,
  Skeleton,
} from '@razorpay/blade/components';

import { PURPOSE_CODE_DESC, PURPOSE_CODE_LINK } from './constants';
import { usePurposeCode } from './states';

const PurposeCode = () => {
  const {
    groups,
    isLoading,
    isReadOnly,
    searchValue,
    purposeCode,
    handleSearch,
    filteredCodes,
    selectedGroup,
    validationState,
    selectedGroupCodes,
    handleValueChange,
    handleClearSearch,
    handleSelectedGroupChange,
  } = usePurposeCode();

  return (
    <Box>
      <Heading size="large" marginBottom="spacing.2">
        Purpose Code
      </Heading>
      <Text>
        {PURPOSE_CODE_DESC}{' '}
        <Link href={PURPOSE_CODE_LINK} target="_blank" rel="noopener">
          Learn more
        </Link>
      </Text>

      <Box
        marginTop="spacing.9"
        paddingX="spacing.1"
        maxHeight="300px"
        overflowY="auto"
        display="flex"
        gap="spacing.6"
        flexDirection="column"
      >
        <Box
          display="grid"
          gridTemplateColumns={{
            base: '1fr 1fr',
            m: '1fr 3fr',
          }}
          gap="spacing.5"
        >
          <TextInput
            showClearButton
            label="Search code"
            placeholder="Ex: P0014"
            leadingIcon={SearchIcon}
            onChange={handleSearch}
            onClearButtonClick={handleClearSearch}
          />
          <Dropdown>
            <SelectInput
              icon={SearchIcon}
              label="Select purpose group"
              labelPosition="top"
              name="item"
              value={selectedGroup}
              onChange={handleSelectedGroupChange}
              placeholder="Select Group"
              validationState="none"
            />
            <DropdownOverlay>
              <ActionList>
                <ActionListItem key="_select" title="All" value="" />
                {groups?.map((group) => (
                  <ActionListItem
                    key={group.purposeGroup}
                    title={group.purposeGroup}
                    value={group.purposeGroup}
                  />
                ))}
              </ActionList>
            </DropdownOverlay>
          </Dropdown>
        </Box>
        <Box maxWidth="500px">
          {!isLoading && filteredCodes ? (
            <RadioGroup
              label={selectedGroup ? `Purpose codes for ${selectedGroup}:` : 'All purpose codes'}
              size="medium"
              isDisabled={isReadOnly}
              value={purposeCode}
              onChange={handleValueChange}
              validationState={validationState.purposeCode.state}
              errorText={validationState.purposeCode.errorText}
              necessityIndicator="required"
            >
              {(selectedGroupCodes || filteredCodes).map((option) => (
                <Radio
                  key={option.purposeCode}
                  helpText={option.helpText}
                  value={option.purposeCode}
                  isDisabled={isReadOnly}
                  marginBottom="spacing.4"
                >
                  {option.purposeCode}: {option.description}
                </Radio>
              ))}
            </RadioGroup>
          ) : null}

          {searchValue && !filteredCodes?.length && <Text>Purpose code not found.</Text>}

          {isLoading && (
            <Box display="grid" gap="spacing.5">
              {[1, 2, 3, 4].map((item) => (
                <Box key={item}>
                  <Skeleton height="20px" width="80px" marginBottom="spacing.3" />
                  <Skeleton height="15px" width="100%" />
                </Box>
              ))}
            </Box>
          )}
        </Box>
      </Box>
    </Box>
  );
};

export default PurposeCode;
