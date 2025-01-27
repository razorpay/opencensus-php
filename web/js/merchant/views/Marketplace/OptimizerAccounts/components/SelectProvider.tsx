import React, { useState, useEffect } from 'react';
import {
  Dropdown,
  DropdownOverlay,
  ActionList,
  ActionListItem,
  ActionListItemAsset,
  AutoComplete,
} from '@razorpay/blade/components';
import { debounce } from 'lodash';
import { gatewayLogos } from 'merchant/views/Optimizer/utils';
import { Provider } from 'merchant/views/Optimizer/types';

export const SelectProvider = ({
  providers,
  selectedProvider,
  setSelectedProvider,
}: {
  providers: Provider[];
  selectedProvider: string;
  setSelectedProvider: (providerId: string) => void;
}): JSX.Element => {
  const [filteredProviders, setFilteredProviders] = useState<Provider[]>([]);
  const [searchProvider, setSearchProvider] = useState<string>('');

  useEffect(() => {
    setFilteredProviders(providers);
  }, [providers]);

  useEffect(() => {
    debounce(() => {
      let searchResult = [...providers];
      if (searchProvider) {
        searchResult = providers.filter((provider) =>
          provider.Provider_name.toLowerCase().includes(searchProvider?.toLowerCase()),
        );
      }
      setFilteredProviders(searchResult);
    }, 300)();
  }, [searchProvider]);

  return (
    <Dropdown selectionType="single" _width="100%">
      <AutoComplete
        name="provider"
        label="Provider"
        labelPosition="top"
        placeholder="Select provider"
        value={selectedProvider}
        onChange={({ values }) => setSelectedProvider(values[0])}
        inputValue={searchProvider}
        onInputValueChange={({ value }) => setSearchProvider(value as string)}
        isRequired
      />
      <DropdownOverlay>
        <ActionList>
          {filteredProviders?.map((provider, index) => (
            <ActionListItem
              key={index}
              leading={
                <ActionListItemAsset src={gatewayLogos[provider.Gateway]} alt={provider.Gateway} />
              }
              title={provider.Provider_name}
              value={provider.Terminal_id}
            />
          ))}
        </ActionList>
      </DropdownOverlay>
    </Dropdown>
  );
};
