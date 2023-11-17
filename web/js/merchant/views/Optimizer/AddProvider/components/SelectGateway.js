import React, { useState } from 'react';
import {
  Box,
  Title,
  Text,
  TextInput,
  Button,
  EditIcon,
  StarIcon,
  CpuIcon,
  GlobeIcon,
  BankIcon,
} from '@razorpay/blade/components';

import debounce from 'common/utils/debounce';
import { SeamlessOption } from 'merchant/views/Navigator/components/Provider/SeamlessOption';
import { gatewayLogos } from 'merchant/views/Navigator/components/util';
import {
  GATEWAY_CATEGORIES,
  RECOMMENDED_GATEWAYS,
  SEAMLESS_NOT_SUPPORTED,
} from 'merchant/views/Navigator/constants';

import { StyledProviderItem, StyledLogoWrapper, StyledProviderLogo, StyledDivider } from './styled';

const GATEWAY_CATEGORY_ICON = {
  aggregators: CpuIcon,
  international_gateways: GlobeIcon,
  bank_gateways: BankIcon,
};

const SelectGateway = (props) => {
  const {
    isEdit,
    isFormEdit,
    providers,
    selectedProvider,
    categorizedProviders = {},
    hasSeamlessOption,
    selectProvider,
    changeGateway,
    validateStep,
    onNextClick,
  } = props;
  const [searchValue, setSearchValue] = useState('');
  const [filteredProviders, setFilteredProviders] = useState(categorizedProviders);

  const categorySectionKeys = Object.keys(filteredProviders);
  const selectedProviderDetails = providers?.[selectedProvider] ?? {};
  const showSeamlessNote = !SEAMLESS_NOT_SUPPORTED.includes(selectedProvider) && !hasSeamlessOption;

  const onSearchClear = () => {
    setSearchValue('');
    setFilteredProviders(categorizedProviders);
  };

  const filterOnSearch = ({ value }) => {
    // Convert the search value to lowercase for case-insensitive matching
    const query = value?.toLowerCase() ?? '';

    if (query.trim() === '') {
      setSearchValue('');
      setFilteredProviders(categorizedProviders);
    } else {
      // Filter the providers based on the search query
      const filteredResults = Object.keys(categorizedProviders).reduce((result, sectionKey) => {
        const filteredCategory = Object.keys(categorizedProviders[sectionKey]).reduce(
          (categoryResult, categoryListKey) => {
            const filteredGateways = categorizedProviders[sectionKey][categoryListKey].filter(
              (gateway) => gateway?.toLowerCase()?.includes(query),
            );
            if (filteredGateways.length > 0) {
              categoryResult[categoryListKey] = filteredGateways;
            }
            return categoryResult;
          },
          {},
        );

        if (Object.keys(filteredCategory).length > 0) {
          result[sectionKey] = filteredCategory;
        }

        return result;
      }, {});
      setSearchValue(value ?? '');
      setFilteredProviders(filteredResults);
    }
  };

  const renderProviderItem = (provider, index) => {
    const providerObj = providers?.[provider] || {};

    const onSelect = (provider) => selectProvider(provider);

    return (
      <StyledProviderItem
        key={`${provider}-${index}`}
        data-testid={provider}
        isClickable={true}
        onClick={() => onSelect(provider)}
      >
        <StyledLogoWrapper>
          <StyledProviderLogo src={gatewayLogos[provider?.toLowerCase()]} alt={provider} />
        </StyledLogoWrapper>
        <Box flex="1 0 auto">
          <Text truncateAfterLines={1}>{providerObj?.['Gateway Name']?.data_value}</Text>
          {RECOMMENDED_GATEWAYS.includes(provider) && (
            <Box display="flex" alignItems="center">
              <StarIcon
                size="small"
                color="feedback.icon.positive.lowContrast"
                marginRight="spacing.1"
              />
              <Text size="small" color="feedback.text.positive.lowContrast">
                Recommended
              </Text>
            </Box>
          )}
        </Box>
      </StyledProviderItem>
    );
  };

  const renderProvidersList = () => {
    if (categorySectionKeys?.length === 0) {
      return (
        <Text
          testID="no-providers-list"
          color="surface.text.subtle.lowContrast"
          textAlign="center"
          size="large"
        >
          Oops! Looks like we couldn&apos;t find any gateway providers.
        </Text>
      );
    }

    if (categorySectionKeys && Array.isArray(categorySectionKeys)) {
      return (
        <Box>
          {categorySectionKeys.map((sectionKey, sectionIndex) => {
            const Icon = GATEWAY_CATEGORY_ICON[sectionKey];
            return (
              <Box key={sectionKey} display="flex" flexDirection="column">
                <Box display="flex" alignItems="center" marginBottom="spacing.6">
                  <Icon color="surface.text.normal.lowContrast" size="medium" />
                  <Text marginLeft="spacing.2">{GATEWAY_CATEGORIES[sectionKey]}</Text>
                </Box>
                <Box display="flex" gap="spacing.6" flexWrap="wrap">
                  {Object.keys(filteredProviders?.[sectionKey])?.map((categoryListKey) => {
                    return (
                      <Box
                        key={categoryListKey}
                        width="200px"
                        display="flex"
                        flexDirection="column"
                        gap="spacing.3"
                      >
                        <Text weight="bold" size="small" type="subtle" truncateAfterLines={1}>
                          {categoryListKey}
                        </Text>
                        {filteredProviders?.[sectionKey]?.[categoryListKey]?.map(
                          renderProviderItem,
                        )}
                      </Box>
                    );
                  })}
                </Box>
                {sectionIndex !== categorySectionKeys.length - 1 && <StyledDivider />}
              </Box>
            );
          })}
        </Box>
      );
    }

    return null;
  };

  return (
    <Box
      display="flex"
      flexDirection="column"
      padding="spacing.7"
      gap={isFormEdit ? 'spacing.9' : 'spacing.6'}
      backgroundColor="surface.background.level2.lowContrast"
    >
      <Box display="flex" justifyContent="space-between">
        <Box display="flex" flexDirection="column" gap="spacing.3">
          <Title color="surface.text.subtle.lowContrast">Select Gateway</Title>
          {isFormEdit && (
            <Text color="surface.text.subtle.lowContrast">
              Select a Gateway for your payment provider
            </Text>
          )}
        </Box>

        {selectedProvider && !isEdit ? (
          <Box display="flex" flexDirection="column" gap="spacing.3" alignItems="end">
            <Button
              data-step="1"
              icon={EditIcon}
              onClick={changeGateway}
              variant="tertiary"
              iconPosition="left"
            >
              Change gateway
            </Button>
          </Box>
        ) : (
          <Box display="flex" flexDirection="column" gap="spacing.3" alignItems="end">
            <Text color="surface.text.subdued.lowContrast">
              {isFormEdit ? 'STEP 1' : hasSeamlessOption ? 'STEP 1 OUT OF 4' : 'STEP 1 OUT OF 3'}
            </Text>
            {!selectedProvider && (
              <Box display="flex" gap="spacing.3">
                <Box minWidth="280px">
                  <TextInput
                    label=""
                    placeholder="Search for a gateway"
                    value={searchValue}
                    onChange={debounce(filterOnSearch, 100)}
                    showClearButton={true}
                    onClearButtonClick={onSearchClear}
                    isDisabled={
                      !searchValue.trim() &&
                      (!categorySectionKeys || categorySectionKeys.length === 0)
                    }
                  />
                </Box>
              </Box>
            )}
          </Box>
        )}
      </Box>

      {selectedProvider ? (
        <Box display="flex" flexDirection="column" gap="spacing.7">
          <Box display="flex" alignItems="center">
            <Box minWidth="180px">
              <Text>Gateway</Text>
            </Box>
            <Box>
              <StyledProviderItem data-testid="selected-provider">
                <Box display="flex" alignItems="center">
                  <Box width="32px" height="32px" marginRight="spacing.3">
                    <StyledLogoWrapper>
                      <StyledProviderLogo
                        src={gatewayLogos?.[selectedProvider?.toLowerCase()]}
                        alt={selectedProvider?.toLowerCase()}
                      />
                    </StyledLogoWrapper>
                  </Box>
                  <Box>
                    <Text weight="bold" truncateAfterLines={1}>
                      {selectedProviderDetails?.['Gateway Name']?.data_value}
                    </Text>
                  </Box>
                </Box>
              </StyledProviderItem>
            </Box>
          </Box>
          {showSeamlessNote && (
            <Box>
              <SeamlessOption
                type="warning"
                providers={providers}
                selectedProvider={selectedProvider}
              />
            </Box>
          )}
        </Box>
      ) : (
        renderProvidersList()
      )}

      {isFormEdit && (
        <Box display="flex" justifyContent="end">
          <Box display="flex" alignItems="center" gap="spacing.7">
            <Button isDisabled={validateStep(1)} onClick={onNextClick}>
              Next
            </Button>
          </Box>
        </Box>
      )}
    </Box>
  );
};

export default SelectGateway;
