import React, { Fragment } from 'react';

import Input from 'common/new-ui/Input';
import Popover, { PopoverBody } from 'common/ui/Popover';
import Tooltip from 'common/ui/Tooltip';

import { WalletsMultiSelect } from './WalletsMultiSelect';
import { getTPVOptions } from 'merchant/views/Navigator/components/AddProvider/util';
import { titleCase } from 'common/utils/rzp-utils';
import { WalletAutoDebit } from './WalletAutoDebit';
import { METHODS, PROVIDER_KEYS } from 'merchant/views/Navigator/constants';

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
    if (!['Gateway Name', 'optimizer_seamless_disabled', PROVIDER_KEYS.SODEXO].includes(label)) {
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

  const isSodexoCheckboxDisabled = isMethodCheckboxDisabled(METHODS.SODEXO);
  const isSodexoEnabled =
    selectedProvider === 'payu' &&
    providers?.[selectedProvider]?.hasOwnProperty(PROVIDER_KEYS.SODEXO);

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
                      <label for="name" className="gateway-detail-title">
                        {label}
                      </label>
                    </div>
                    <div className="col-xs-9">
                      <div>
                        {data_value
                          .filter((method) => {
                            if (method === 'upi' && Gateway_details?.optimizer_seamless_disabled) {
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
                    <label for="name" className="gateway-detail-title">
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
        }

        return (
          <div className="col-xs-12" key={label}>
            <div className="row">
              <div className="col-xs-3">
                <label for="name" className="gateway-detail-title">
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
                    <label for="name" className="gateway-detail-title">
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
