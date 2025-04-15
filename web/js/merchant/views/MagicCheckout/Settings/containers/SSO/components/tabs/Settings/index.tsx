import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';

import {
  Accordion,
  useToast,
  AccordionItem,
  AccordionItemBody,
  AccordionItemHeader,
  Box,
  Checkbox,
  CheckboxGroup,
  InfoIcon,
  Link,
  Radio,
  RadioGroup,
  TextInput,
  Collapsible,
  CollapsibleLink,
  CollapsibleBody,
  Tooltip,
  TooltipInteractiveWrapper,
} from '@razorpay/blade/components';

import {
  CUSTOMER_CONSENT_OPTIONS,
  DEFAULT_SSO_CONFIG,
  EMAIL_FLOW_OPTIONS,
  WIDGET_TIMER_OPTIONS,
} from 'merchant/views/MagicCheckout/Settings/containers/SSO/constants';
import { useSSOContext } from 'merchant/views/MagicCheckout/Settings/containers/SSO/context/index';
import {
  getSSOConfigPayload,
  updateSSOStore,
} from 'merchant/views/MagicCheckout/Settings/containers/SSO/context/helpers';
import { saveSSOSettings } from 'merchant/views/MagicCheckout/Settings/containers/SSO/api';
import { constructPayloadObjects } from 'merchant/views/MagicCheckout/Settings/containers/SSO/utils';
import SSOButtons from '@dashboards/payments/views/MagicCheckout/Settings/containers/SSO/components/common/Buttons';
import {
  CustomerConsentType,
  EmailFlowType,
  LoginScreenOption,
} from 'merchant/views/MagicCheckout/Settings/containers/SSO/types';
import { BladeFormInputOnEvent } from 'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/v2/types';

const Settings = () => {
  const [isLoading, setIsLoading] = useState(false);
  const [isMandatoryCheckboxChecked, setIsMandatoryCheckboxChecked] = useState(false);
  const [pendingChanges, setPendingChanges] = useState<{
    selectedOptions: string[];
    optionDelays: Record<string, string | number>;
  }>({
    selectedOptions: [],
    optionDelays: {},
  });

  const { show } = useToast();
  const ssoContext = useSSOContext();
  const navigate = useNavigate();
  const {
    isSSOEnabled,
    ssoWidget,
    ssoSettings,
    updateLoginScreenOptions,
    updateCustomerConsent,
    updateEmailFlow,
    merchantId,
  } = ssoContext;

  useEffect(() => {
    if (!ssoSettings?.loginScreenOptions) return;

    const selectedOptions = ssoSettings.loginScreenOptions.map((opt) => opt.type);

    const optionDelays = ssoSettings.loginScreenOptions.reduce((acc, opt) => {
      if (opt.delay !== null) acc[opt.type] = opt.delay;
      return acc;
    }, {});

    setPendingChanges({
      selectedOptions,
      optionDelays,
    });

    if (
      selectedOptions.includes('checkout_init') &&
      ssoSettings.loginScreenOptions.find((opt) => opt.type === 'checkout_init')?.mandatory
    ) {
      setIsMandatoryCheckboxChecked(true);
    }

    if (!ssoSettings.customerConsent) {
      updateCustomerConsent(ssoSettings.customerConsent);
    }

    if (!ssoSettings.emailFlow) {
      updateEmailFlow(ssoSettings.emailFlow);
    }
  }, [ssoSettings?.loginScreenOptions]);

  // Create new login screen options from pending changes
  const createNewLoginScreenOptions = useCallback(() => {
    return constructPayloadObjects(
      pendingChanges.selectedOptions,
      pendingChanges.optionDelays,
      isMandatoryCheckboxChecked,
    ) as LoginScreenOption[];
  }, [pendingChanges]);

  const handleCheckboxChange = ({ values }) => {
    setPendingChanges((prev) => ({
      ...prev,
      selectedOptions: values,
    }));
  };

  const handleInputChange = ({ name, value }: { name?: string; value?: string }) => {
    if (!name || !value) return;

    setPendingChanges((prev) => ({
      ...prev,
      optionDelays: {
        ...prev.optionDelays,
        [name]: value,
      },
    }));
  };

  const handleConsentRadioOnchange = ({ value }: BladeFormInputOnEvent) => {
    updateCustomerConsent(value as CustomerConsentType);
  };

  const handleEmailFlowRadioOnchange = ({ value }: BladeFormInputOnEvent) => {
    updateEmailFlow(value as EmailFlowType);
  };

  // Reset settings
  const handleReset = () => {
    try {
      const currentConfig = getSSOConfigPayload(DEFAULT_SSO_CONFIG, merchantId);
      updateSSOStore({ ...currentConfig.configs.sso_config, sso_enabled: true }, ssoContext);
      show({
        type: 'informational',
        content: 'Settings reset successful',
        color: 'positive',
      });
    } catch (error) {
      show({
        type: 'informational',
        content: 'Settings reset failed',
        color: 'negative',
      });
    }
  };

  // Save changes
  const handleSaveChanges = useCallback(async () => {
    setIsLoading(true);

    const newOptions = createNewLoginScreenOptions();
    updateLoginScreenOptions(newOptions);

    const storeConfig = {
      isSSOEnabled,
      ssoSettings: {
        ...ssoSettings,
        loginScreenOptions: newOptions,
      },
      ssoWidget,
    };

    const payload = getSSOConfigPayload(storeConfig, merchantId);
    await saveSSOSettings(payload)
      .then(() => {
        show({
          type: 'informational',
          content: 'Updated successfully',
          color: 'positive',
        });
      })
      .catch(() => {
        show({
          type: 'informational',
          content: 'Update failed',
          color: 'negative',
        });
      })
      .finally(() => {
        setIsLoading(false);
      });
  }, [isSSOEnabled, ssoSettings, ssoWidget, pendingChanges, createNewLoginScreenOptions]);

  // Navigate to next tab
  const handleNext = async () => {
    try {
      await handleSaveChanges();
      navigate('/magic/settings/sso/customise');
    } catch (error) {
      show({
        type: 'informational',
        content: 'save sso config failed',
        color: 'negative',
      });
    }
  };

  return (
    <Box display={'flex'} flexDirection={'column'} alignItems="flex-end" marginLeft={'spacing.6'}>
      <Accordion marginTop="spacing.8" variant="filled" alignSelf={'flex-start'}>
        <AccordionItem>
          <AccordionItemHeader
            title="Where should we display login screen?"
            subtitle="Select where to show the login screen, if user isn't logged in"
          />
          <AccordionItemBody>
            <CheckboxGroup
              necessityIndicator="none"
              onChange={handleCheckboxChange}
              value={pendingChanges.selectedOptions}
            >
              {WIDGET_TIMER_OPTIONS.map(({ label, value, sublabel, subcheckbox }) => (
                <Box paddingLeft="0px" display="flex" flexDirection="column" key={value}>
                  <Box display="flex">
                    <Checkbox key={value} value={value}>
                      {label}
                    </Checkbox>
                  </Box>
                  {pendingChanges.selectedOptions.includes(value) && sublabel && (
                    <Collapsible>
                      <CollapsibleLink size="medium">{sublabel}</CollapsibleLink>
                      <CollapsibleBody width="100%">
                        <Box marginTop="spacing.2" padding="4px" borderRadius="medium">
                          <TextInput
                            key={value}
                            value={String(pendingChanges.optionDelays[value] || '0')}
                            label="Show login widget after"
                            labelPosition="top"
                            name={value}
                            onChange={handleInputChange}
                            placeholder="0"
                            size="medium"
                            trailingButton={
                              <Link variant="button" color="neutral" isDisabled={true}>
                                Seconds
                              </Link>
                            }
                          />
                        </Box>
                      </CollapsibleBody>
                    </Collapsible>
                  )}

                  {pendingChanges.selectedOptions.includes(value) && subcheckbox ? (
                    <Box marginTop="spacing.2" padding="4px" borderRadius="medium">
                      <Checkbox
                        isChecked={isMandatoryCheckboxChecked}
                        key={`${value}_mandatory_login`}
                        onChange={() => setIsMandatoryCheckboxChecked(!isMandatoryCheckboxChecked)}
                        size="small"
                        value={`${value}_mandatory_login`}
                        marginLeft={'10px'}
                      >
                        {subcheckbox}
                      </Checkbox>
                    </Box>
                  ) : null}
                </Box>
              ))}
            </CheckboxGroup>
          </AccordionItemBody>
        </AccordionItem>

        <AccordionItem>
          <AccordionItemHeader
            title="Ask consent from customer for marketing communication"
            subtitle="Select between single, separate or none"
          />
          <AccordionItemBody>
            <RadioGroup
              necessityIndicator="none"
              size="medium"
              value={ssoSettings?.customerConsent || ''}
              onChange={handleConsentRadioOnchange}
            >
              {CUSTOMER_CONSENT_OPTIONS.map(({ label, value }) => (
                <Radio key={value} value={value}>
                  {label}
                </Radio>
              ))}
            </RadioGroup>
          </AccordionItemBody>
        </AccordionItem>

        <AccordionItem>
          <AccordionItemHeader
            title="Collect missing emails from everyone"
            subtitle="In the case of missing emails, we can gather that during login"
          />
          <AccordionItemBody>
            <RadioGroup
              necessityIndicator="none"
              size="medium"
              value={ssoSettings?.emailFlow || ''}
              onChange={handleEmailFlowRadioOnchange}
            >
              {EMAIL_FLOW_OPTIONS.map(({ label, value, toolTipText }) => (
                <Box display="flex" alignItems="baseLine" gap="5px" key={value}>
                  <Radio key={value} value={value}>
                    {label}
                  </Radio>
                  <Tooltip content={toolTipText} placement="top">
                    <TooltipInteractiveWrapper>
                      <InfoIcon size="medium" color="surface.icon.gray.muted" />
                    </TooltipInteractiveWrapper>
                  </Tooltip>
                </Box>
              ))}
            </RadioGroup>
          </AccordionItemBody>
        </AccordionItem>
      </Accordion>

      <SSOButtons
        onReset={handleReset}
        onSaveChanges={handleSaveChanges}
        onNext={handleNext}
        isLoading={isLoading}
      />
    </Box>
  );
};

export default Settings;
