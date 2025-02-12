import React, { Fragment } from 'react';

import Input from 'common/new-ui/Input';
import SwitchField from 'common/ui/Forms/SwitchField';
import Popover, { PopoverBody } from 'common/ui/Popover';
import Tooltip from 'common/ui/Tooltip';
import { titleCase } from 'common/utils/rzp-utils';
import { getTPVOptions } from 'merchant/views/Navigator/components/AddProvider/util';
import {
  METHODS,
  PROVIDER_KEYS,
  INSTANT_PROVIDER_UNSUPPORTED_METHODS,
  SKIP_INPUT_FOR_PROVIDER_KEYS,
} from 'merchant/views/Navigator/constants';

import { WalletAutoDebit } from './WalletAutoDebit';
import { WalletsMultiSelect } from './WalletsMultiSelect';

export function Step3({
  isEdit,
  selectedProvider,
  providers,
  provider,
  validationErrors,
  changeGatewayDetails,
  changeGatewayWallets,
  changeEnableAutoDebitSwitch,
  user,
  toggleMethods,
}) {
  const selectedProviderDetails = providers?.[selectedProvider] ?? {};
  const { Gateway_details } = provider;
  const walletOptions =
    selectedProviderDetails?.['Payment Methods']?.meta_data?.wallet_metadata?.wallets ?? [];

  const PAYTM_AUTO_DEBIT_FIELDS = ['ENABLE_AUTO_DEBIT', 'CLIENT_KEY', 'CLIENT_SECRET'];

  const paytmAutoDebitFields = [0, 1, 2];

  // Filter out the fields that are required in this step i.e step 3.
  const fields = Object.entries(selectedProviderDetails).reduce((acc, [label, value]) => {
    if (!SKIP_INPUT_FOR_PROVIDER_KEYS.includes(label)) {
      // Need to show auto debit fields at the end of the list in mentioned order
      if (selectedProvider === 'paytm' && PAYTM_AUTO_DEBIT_FIELDS.includes(label)) {
        if (label === 'ENABLE_AUTO_DEBIT') {
          paytmAutoDebitFields[0] = { label, ...value };
        } else if (label === 'CLIENT_KEY') {
          paytmAutoDebitFields[1] = { label, ...value };
        } else if (label === 'CLIENT_SECRET') {
          paytmAutoDebitFields[2] = { label, ...value };
        }
      } else {
        acc.push({ label, ...value });
      }
    }
    return acc;
  }, []);

  function isMethodCheckboxDisabled(method) {
    if (!isEdit) return true;

    // Paytm onboarding enabled wallet method by default
    if (selectedProvider === 'paytm' && method === 'wallet') return true;

    // Payu disable sodexo if card method is not selected
    if (
      method === METHODS.SODEXO &&
      !provider?.Gateway_details?.['Payment Methods']?.includes(METHODS.CARD)
    ) {
      return true;
    }

    return false;
  }

  const isS2SEnabled = !Gateway_details?.optimizer_seamless_disabled;
  const isSodexoCheckboxDisabled = isMethodCheckboxDisabled(METHODS.SODEXO);
  const isSodexoEnabled =
    isS2SEnabled && selectedProviderDetails?.hasOwnProperty(PROVIDER_KEYS.SODEXO);

  const handleSwitch = (isChecked, label) => {
    const event = {
      target: {
        type: 'bool',
        checked: isChecked,
        name: label,
      },
    };
    changeGatewayDetails(event);
  };

  const shouldShowField = (fieldLabel) => {
    if (fieldLabel === 'Recurring') {
      // to enable 'Recurring' either 'card' or 'upi' method should be enabled
      return Gateway_details?.['Payment Methods']?.some(
        (method) => method === METHODS.CARD || method === METHODS.UPI,
      );
    }

    return false;
  };

  return (
    <div className="row">
      {fields.map(({ label = '', data_type, data_value }) => {
        if (data_type === 'array') {
          if (label === 'Payment Methods') {
            return (
              <Fragment key={label}>
                <div className="col-xs-12">
                  <div className="row">
                    <div className="col-xs-3">
                      <label htmlFor="name" className="gateway-detail-title">
                        {label}
                      </label>
                    </div>
                    <div className="col-xs-9">
                      <div>
                        {data_value
                          .filter((method) => {
                            // For Instant on-boarding there are certain methods not supported
                            const IS_METHOD_UPSUPPORTED =
                              Gateway_details?.optimizer_seamless_disabled &&
                              INSTANT_PROVIDER_UNSUPPORTED_METHODS[selectedProvider]?.includes(
                                method,
                              );
                            if (IS_METHOD_UPSUPPORTED) {
                              return false;
                            }
                            return true;
                          })
                          .map((method) => (
                            <span className="payment-method-checkbox-span" key={method}>
                              <Input.Check
                                id={method}
                                fieldLabel={method}
                                checked={provider?.Gateway_details?.['Payment Methods']?.includes(
                                  method,
                                )}
                                onChange={(e) => changeGatewayDetails(e, method)}
                                disabled={isMethodCheckboxDisabled(method)}
                                autoRender
                              />
                            </span>
                          ))}
                        {isSodexoEnabled ? (
                          <span className="payment-method-checkbox-span">
                            <Input.Check
                              id={PROVIDER_KEYS.SODEXO}
                              fieldLabel={METHODS.SODEXO}
                              checked={provider?.Gateway_details?.Sodexo ?? false}
                              onChange={toggleMethods}
                              disabled={isSodexoCheckboxDisabled}
                              autoRender
                            />
                            {isSodexoCheckboxDisabled ? (
                              <Tooltip delay={50} align="bottom">
                                To activate the Sodexo feature, please make sure to enable the card
                                option
                              </Tooltip>
                            ) : null}
                          </span>
                        ) : null}
                      </div>
                      <p className="select-payment-method-desc">
                        Select the payment methods to be enabled for the {selectedProvider}
                        {selectedProvider === 'paytm' &&
                          '. Paytm wallet will be enabled by default.'}
                      </p>
                    </div>
                  </div>
                </div>

                {walletOptions?.length > 0 &&
                  provider?.Gateway_details?.['Payment Methods']?.includes('wallet') && (
                    <WalletsMultiSelect
                      walletOptions={walletOptions}
                      walletSelected={provider?.Gateway_details?.wallet_metadata?.wallets}
                      changeGatewayWallets={changeGatewayWallets}
                      disabled={selectedProvider === 'paytm'} // For paytm wallets get enabled by default
                    />
                  )}
              </Fragment>
            );
          } else if (label === 'TPV') {
            return (
              <div className="col-xs-12" key={label}>
                <div className="row tpv-field-wrapper">
                  <div className="col-xs-3">
                    <label htmlFor="name" className="gateway-detail-title">
                      <span>{label}</span>
                      <small className="help-content ml-4">
                        <i className="i i-info-circle" />
                        <Popover align="top" theme="dark">
                          <PopoverBody>
                            <div>
                              Third-Party Validation (TPV) of your customer’s bank accounts in
                              real-time. It is a mandatory requirement for merchants in the BFSI
                              (Banking, Financial Services and Insurance) sector.
                            </div>
                          </PopoverBody>
                        </Popover>
                      </small>
                    </label>
                  </div>

                  <div className="col-xs-9">
                    <Input.Radio
                      id={label}
                      defaultValue={provider.Gateway_details?.TPV ?? -1}
                      options={getTPVOptions(data_value)}
                      name={label.toLowerCase()}
                      onChange={changeGatewayDetails}
                      disabled={!isEdit}
                      autoRender
                    />
                  </div>
                </div>
              </div>
            );
          }
        } else if (data_type === 'bool' && shouldShowField(label)) {
          return (
            <div className="col-xs-12" key={label}>
              <div className="row">
                <div className="col-xs-3">
                  <label htmlFor="name" className="gateway-detail-title">
                    <span>{titleCase(label)}</span>
                  </label>
                </div>
                <div className="col-xs-9">
                  <div className="auto-debit-switch-wrapper">
                    <SwitchField
                      type="prime round"
                      defaultChecked={provider?.Gateway_details?.[label] ?? false}
                      onChange={(isChecked) => handleSwitch(isChecked, label)}
                    />
                    <span>{provider?.Gateway_details?.[label] ? 'Enabled' : 'Disabled'}</span>
                  </div>
                  <p className="select-payment-method-desc">
                    Available for Card and Netbanking, coming soon for UPI.
                  </p>
                </div>
              </div>
            </div>
          );
        } else if (data_type === 'string') {
          return (
            <div className="col-xs-12" key={label}>
              <div className="row">
                <div className="col-xs-3">
                  <label htmlFor="name" className="gateway-detail-title">
                    {label}
                  </label>
                </div>
                <div className="col-xs-6">
                  {!isEdit ? (
                    <label className="provider-details-read-only">
                      {provider?.Gateway_details?.[label] || ''}
                    </label>
                  ) : (
                    <>
                      <Input
                        id={label}
                        name={label?.toLowerCase()}
                        type={data_type === 'string' ? 'text' : 'number'}
                        value={provider?.Gateway_details?.[label] || ''}
                        placeholder={data_value}
                        onChange={changeGatewayDetails}
                      />
                      {validationErrors?.[label] && (
                        <div className="provider-details-validation-error">
                          {validationErrors[label]}
                        </div>
                      )}
                    </>
                  )}
                </div>
              </div>
            </div>
          );
        }
        return null;
      })}
      {selectedProvider === 'paytm' &&
        user.isPaytmAutoDebitEnabled &&
        paytmAutoDebitFields?.map(({ label = '', data_type, data_value }) => {
          if (label === 'ENABLE_AUTO_DEBIT') {
            return (
              <WalletAutoDebit
                key={label}
                label={label}
                provider={provider}
                changeEnableAutoDebitSwitch={changeEnableAutoDebitSwitch}
              />
            );
          }
          if (provider?.Gateway_details?.ENABLE_AUTO_DEBIT) {
            return (
              <div className="col-xs-12" key={label}>
                <div className="row">
                  <div className="col-xs-3">
                    <label htmlFor="name" className="gateway-detail-title">
                      {titleCase(label)}
                    </label>
                  </div>
                  <div className="col-xs-6">
                    {!isEdit ? (
                      <label className="provider-details-read-only">
                        {provider?.Gateway_details?.[label] || ''}
                      </label>
                    ) : (
                      <>
                        <Input
                          id={label}
                          name={label?.toLowerCase()}
                          type={data_type === 'string' ? 'text' : 'number'}
                          value={provider?.Gateway_details?.[label] || ''}
                          placeholder={data_value}
                          onChange={changeGatewayDetails}
                        />
                        {validationErrors[label] && (
                          <div className="provider-details-validation-error">
                            {validationErrors[label]}
                          </div>
                        )}
                      </>
                    )}
                  </div>
                </div>
              </div>
            );
          }
          return null;
        })}
    </div>
  );
}
