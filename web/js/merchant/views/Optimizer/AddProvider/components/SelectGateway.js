import React, { useState } from 'react';
import {
  Box,
  Heading,
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
import DocsLink from 'merchant/components/DocsLink';

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
    hasAccountTypeOption,
    selectProvider,
    changeGateway,
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
          <StyledProviderLogo
            src={
              providerObj?.['Gateway Name']?.meta_data?.image_url ??
              gatewayLogos[provider?.toLowerCase()]
            }
            alt={provider}
          />
        </StyledLogoWrapper>
        <Box flex="1 0 auto">
          <Text truncateAfterLines={1}>{providerObj?.['Gateway Name']?.data_value}</Text>
          {RECOMMENDED_GATEWAYS.includes(provider) && (
            <Box display="flex" alignItems="center">
              <StarIcon
                size="small"
                color="feedback.icon.positive.intense"
                marginRight="spacing.1"
              />
              <Text size="small" color="feedback.text.positive.intense">
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
          color="surface.text.gray.subtle"
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
                  <Icon color="interactive.icon.gray.normal" size="medium" />
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
                        <Text
                          weight="semibold"
                          size="small"
                          truncateAfterLines={1}
                          color="surface.text.gray.subtle"
                        >
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
      gap={isFormEdit ? 'spacing.4' : 'spacing.6'}
      backgroundColor="surface.background.gray.intense"
    >
      <Box display="flex" justifyContent="space-between">
        <Box display="flex" flexDirection="column" gap="spacing.3">
          <Heading color="surface.text.gray.subtle" size="large">
            Select Gateway
          </Heading>
          {isFormEdit && (
            <Text color="surface.text.gray.subtle">Select a Gateway for your payment provider</Text>
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
            <Text color="surface.text.gray.muted">
              {isFormEdit
                ? 'STEP 1'
                : hasSeamlessOption || hasAccountTypeOption
                ? 'STEP 1 OUT OF 4'
                : 'STEP 1 OUT OF 3'}
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
      <Box display="flex" justifyContent="end">
        <DocsLink url="OPTIMIZER_DOC_URL" />
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
                        src={
                          selectedProviderDetails?.['Gateway Name']?.meta_data?.image_url ??
                          gatewayLogos?.[selectedProvider?.toLowerCase()]
                        }
                        alt={selectedProvider?.toLowerCase()}
                      />
                    </StyledLogoWrapper>
                  </Box>
                  <Box>
                    <Text weight="semibold" truncateAfterLines={1}>
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
    </Box>
  );
};

export default SelectGateway;
