import React, { useEffect, useState } from 'react';
import debounce from 'lodash/debounce';

import Input from 'common/new-ui/Input';
import Spinner from 'common/ui/Spinner';
import SeamlessNote from 'merchant/views/Navigator/components/Provider/SeamlessNote';
import { SeamlessOption } from 'merchant/views/Navigator/components/Provider/SeamlessOption';
import Select from 'merchant/views/Navigator/components/Select';
import { gatewayLogos } from 'merchant/views/Navigator/components/util';
import {
  RECOMMENDED_GATEWAYS,
  SEAMLESS_CONTENT,
  SEAMLESS_PROVIDERS,
  SEAMLESS_OPTIONS,
  SEAMLESS_NOT_SUPPORTED,
  ACCOUNT_TYPE_OPTIONS,
  RAZORPAY_GATEWAY_KEY,
} from 'merchant/views/Navigator/constants';
import { trackOptimizerEvents } from 'merchant/views/Navigator/track';

/**
 * Instant(beta) -> optimizer_seamless_disabled is true, which means seamless option is disabled
 * Server-to-Server -> optimizer_seamless_disabled is false, which means seamless option is enabled
 */

export const Step1 = (props) => {
  const {
    steps,
    providers,
    loadingProviders,
    selectProvider,
    selectedProvider,
    changeGateway,
    toggleSeamless,
    isEdit,
    gatewayDetails,
    changeGatewayDetails,
  } = props;

  const [search, setSearch] = useState('');
  const [filteredProviders, setFilteredProviders] = useState({});
  const [isBankingVasAccount, setIsBankingVasAccount] = useState(null);
  const [bankingVasList, setBankingVasList] = useState([]);
  const [selectedBank, setSelectedBank] = useState([]);

  useEffect(() => {
    if (selectedProvider === RAZORPAY_GATEWAY_KEY) {
      const list = providers?.[selectedProvider]?.['Gateway Acquirer']?.data_value?.map(
        (bank, index) => {
          return {
            id: index,
            name: bank.name,
            value: bank.value,
          };
        },
      );
      setBankingVasList(list);
    }
  }, [selectedProvider, providers]);

  const providersObjectKeys = Object.keys(providers) ?? [];
  const selectedProviderDetails = providers?.[selectedProvider] ?? {};
  const showSeamlessNote =
    selectedProvider && !SEAMLESS_NOT_SUPPORTED.includes(selectedProvider) && steps[1].edit;
  const seamlessOptionExist =
    SEAMLESS_PROVIDERS?.includes(selectedProvider) &&
    providers?.[selectedProvider]?.hasOwnProperty('optimizer_seamless_disabled');
  const showAccountType = selectedProvider === RAZORPAY_GATEWAY_KEY && steps[1].edit;

  const filterOnSearch = (e) => {
    const val = e.target.value;
    if (val.trim() === '') {
      setSearch('');
      setFilteredProviders({});
    } else {
      const gatewayList = providersObjectKeys.map((key) =>
        key === RAZORPAY_GATEWAY_KEY ? 'razorpay' : key,
      );
      const res = gatewayList.reduce((obj, item) => {
        if (item?.toLowerCase()?.startsWith(val.toLowerCase())) {
          if (item === 'razorpay') {
            obj[RAZORPAY_GATEWAY_KEY] = providers[RAZORPAY_GATEWAY_KEY];
          } else {
            obj[item] = providers[item];
          }
        }
        return obj;
      }, {});

      setSearch(val);
      setFilteredProviders(res);
    }
  };

  const renderProviderItem = (provider, index) => {
    const SELECTED_PROVIDER = providers?.[provider] || {};
    const PAYMENT_METHODS = SELECTED_PROVIDER?.['Payment Methods']?.data_value?.join(', ') ?? '';

    const onSelect = () => selectProvider(provider);

    return (
      <div
        key={`${provider}-${index}`}
        className="col-xs-4 gateway-provider-col"
        data-testid="gateway-provider"
      >
        <div className="gateway-provider-block" onClick={onSelect}>
          <div className="provider-img-holder">
            <img alt={provider} src={gatewayLogos[provider?.toLowerCase()]} />
          </div>
          <div className="gateway-provider-block--details">
            <h3>{SELECTED_PROVIDER?.['Gateway Name']?.data_value}</h3>
            <div className="gateway-provider-block--details--methods">
              <p title={PAYMENT_METHODS}>{PAYMENT_METHODS}</p>
            </div>
          </div>
        </div>
      </div>
    );
  };

  const renderProvidersList = () => {
    if (Object.keys(filteredProviders).length > 0) {
      return Object.keys(filteredProviders).map((provider, index) => {
        return renderProviderItem(provider, index);
      });
    }

    if (!search && providersObjectKeys.length > 0) {
      return (
        <>
          <div className="col-xs-12 popular-gateways-header my-2">
            <img
              alt="popular"
              src="https://cdn.razorpay.com/static/assets/merchant-dash/popular_provider.svg"
            />
            <span>Popular Gateways</span>
          </div>
          {RECOMMENDED_GATEWAYS.map((provider, index) => renderProviderItem(provider, index))}
          <div className="col-xs-12 all-gateways-header mb-2">All Gateways</div>
          {providersObjectKeys.map((provider, index) => {
            return renderProviderItem(provider, index);
          })}
        </>
      );
    }

    return <div className="col-xs-12 all-gateways-header mb-2">No Providers Found</div>;
  };

  const handleGatewayChange = () => {
    setSearch('');
    setFilteredProviders({});
    changeGateway();
  };

  const onRadioChange = ({ target }) => {
    const parsedValue = target.value === 'true';
    toggleSeamless(parsedValue);
  };

  const radioFeedback = () => {
    const contentExist = SEAMLESS_CONTENT?.hasOwnProperty(selectedProvider);
    const seamlessRadioValue = gatewayDetails?.hasOwnProperty('optimizer_seamless_disabled');

    if (!(contentExist && seamlessRadioValue)) return null;

    return (
      <SeamlessNote
        type="info"
        seamlessDisabled={gatewayDetails?.optimizer_seamless_disabled}
        providers={providers}
        selectedProvider={selectedProvider}
        isEdit={isEdit}
      />
    );
  };

  const onAccountTypeChange = ({ target }) => {
    const isBankingVasAccountVal = target.value === 'true';
    setIsBankingVasAccount(isBankingVasAccountVal);
    const gatewayAcquirer = isBankingVasAccountVal ? '' : 'razorpay';
    trackOptimizerEvents({
      screen: `Optimizer ${isEdit ? 'Edit' : 'Add'} Provider`,
      objectName: 'account type',
      actionName: 'select',
      properties: {
        'Account Type': isBankingVasAccountVal ? 'Banking VAS' : 'Razorpay',
      },
    });
    changeGatewayDetails({
      target: { name: 'Gateway Acquirer', id: 'Gateway Acquirer', value: gatewayAcquirer },
    });
  };

  const changeBank = (value) => {
    setSelectedBank(value);
    const bankingVas = value[0].value;
    trackOptimizerEvents({
      screen: `Optimizer ${isEdit ? 'Edit' : 'Add'} Provider`,
      objectName: 'bank',
      actionName: 'select',
      properties: {
        Bank: bankingVas,
      },
    });
    changeGatewayDetails({
      target: { name: 'Gateway Acquirer', id: 'Gateway Acquirer', value: bankingVas },
    });
  };

  if (loadingProviders) {
    return (
      <div className="page-spinner-container">
        <Spinner />
      </div>
    );
  }

  return (
    <div>
      <div className="row gateway-section-row">
        <div className="col-xs-2">
          <label htmlFor="description" className="title-left">
            Gateway
          </label>
        </div>

        <div className="col-xs-6">
          {!steps[1].edit && selectedProvider ? (
            <label className="provider-details-read-only" data-testid="provider-readOnly">
              <div className="provider-logo-holder">
                <img src={gatewayLogos?.[selectedProvider?.toLowerCase()]} alt="gateway-logo" />
              </div>
              {selectedProviderDetails?.['Gateway Name']?.data_value}
            </label>
          ) : selectedProvider ? (
            <>
              <div className="selected-gateway-provider" data-testid="selected-gateway">
                <div className="provider-img-holder">
                  <img src={gatewayLogos?.[selectedProvider?.toLowerCase()]} alt="gateway-logo" />
                </div>
                <div className="gateway-provider-block--details">
                  <h3>{selectedProviderDetails?.['Gateway Name']?.data_value}</h3>
                  <p className="gateway-provider-block--details--methods">
                    {selectedProviderDetails?.['Payment Methods']?.data_value?.join(', ')}
                  </p>
                </div>
              </div>
              {steps[1].edit && !isEdit && (
                <div className="change-gateway" onClick={handleGatewayChange}>
                  <i className="i i-pencil-edit" /> Change Gateway
                </div>
              )}
            </>
          ) : (
            <Input
              addonBefore={<i className="i i-search" />}
              type="text"
              name="gateway"
              placeholder="Search Gateway"
              aria-label="Search Gateway"
              className="Input--vLeft"
              onChange={debounce(filterOnSearch, 300)}
            />
          )}
        </div>
      </div>

      {!selectedProvider && (
        <div className="row list-providers-section">
          <div className="col-xs-10 col-xs-offset-2 p-0">
            <div className="row">{renderProvidersList()}</div>
          </div>
        </div>
      )}

      {showSeamlessNote && seamlessOptionExist && (
        <div className="row mt-1">
          <div className="col-xs-2">
            <label htmlFor="description" className="title-left mt-1">
              Integration type
            </label>
          </div>
          <div className="col-xs-10">
            <Input.Radio
              className="Input--vTop Input--capitalize"
              name="optimizer_seamless_disabled"
              size="small"
              noDefaultSelectedValue={true}
              defaultValue={gatewayDetails?.optimizer_seamless_disabled}
              options={SEAMLESS_OPTIONS}
              onChange={onRadioChange}
              description={radioFeedback}
            />
          </div>
        </div>
      )}

      {showSeamlessNote && !seamlessOptionExist && (
        <div className="row mt-1 mb-2">
          <div className="col-xs-10 col-xs-offset-2">
            <SeamlessOption
              type="warning"
              providers={providers}
              selectedProvider={selectedProvider}
            />
          </div>
        </div>
      )}

      {showAccountType && (
        <div className="row mt-1">
          <div className="col-xs-2">
            <label htmlFor="account type" className="title-left mt-1">
              Account type
            </label>
          </div>
          <div className="col-xs-10">
            <Input.Radio
              className="Input--vTop Input--capitalize"
              name="account_type"
              size="small"
              noDefaultSelectedValue={true}
              defaultValue={isBankingVasAccount}
              options={ACCOUNT_TYPE_OPTIONS}
              onChange={onAccountTypeChange}
            />
          </div>
        </div>
      )}

      {showAccountType && isBankingVasAccount && (
        <div className="row mt-1">
          <div className="col-xs-2">
            <label htmlFor="bank" className="title-left mt-1">
              Bank
            </label>
          </div>
          <div className="col-xs-4">
            <Select
              multiple={false}
              placeholder="Select bank"
              options={bankingVasList}
              searchable
              selected={selectedBank}
              select={changeBank}
            />
          </div>
        </div>
      )}
    </div>
  );
};
