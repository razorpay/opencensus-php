import React, { useState, useEffect } from 'react';
import {
  Box,
  IconButton,
  ArrowLeftIcon,
  Heading,
  Link,
  ExternalLinkIcon,
  Spinner,
  Dropdown,
  SelectInput,
  SearchIcon,
  DropdownOverlay,
  ActionList,
  ActionListSection,
  ActionListItem,
  Button,
  ArrowRightIcon,
} from '@razorpay/blade/components';

import { useSplitzService } from 'common/splitz';
import {
  SAVE_GATEWAY_DOC_LINK,
  POPULAR_GATEWAYS,
} from 'merchant/views/Optimizer/OnBoarding/constants';
import {
  SAVE_GATEWAY_PAGE_VISIT,
  SAVE_GATEWAY_BACK_CLICK,
  saveGatewayCredsClick,
  submitDetailsClick,
} from 'merchant/views/Optimizer/OnBoarding/track';
import { SaveGatewayDiv, SaveGatewayFooter } from 'merchant/views/Optimizer/OnBoarding/styled';
import {
  isIntegrationAuditEnabled,
  isGatewaySupportIntegrationAudit,
} from 'merchant/views/Optimizer/AddProvider/utils';
import { addProviderV3 } from 'merchant/views/Optimizer/AddProvider/service';
import { addProvider } from 'merchant/views/Navigator/service';
import { trackOptimizerEvents } from 'merchant/views/Optimizer/track';
import { fetchSupportedGateways } from 'merchant/views/Optimizer/service';
import {
  filterPopularGateways,
  handleTPVFeatures,
} from 'merchant/views/Optimizer/OnBoarding/utils';
import { GatewayCreds } from './GatewayCreds';

export const SaveGateway = ({
  prevStep,
  showNotification,
}: {
  prevStep: (success?: boolean) => void;
  showNotification: (payload: Record<string, unknown>) => void;
}): JSX.Element => {
  const splitz = useSplitzService();
  const [loadingSupportedGateways, setLoadingSupportedGateways] = useState(true);
  const [supportedGateways, setSupportedGateways] = useState<Record<string, unknown>>({});
  const [supportedGatewaysList, setSupportedGatewaysList] = useState<
    { label: string; value: string }[]
  >([]);
  const [selectedGateways, setSelectedGateways] = useState<string[]>([]);
  const [gatewayDetails, setGatewayDetails] = useState<Record<string, Record<string, string>>>({});
  const [isSaving, setIsSaving] = useState<boolean>(false);
  const [savedGateway, setSavedGateway] = useState<string[]>([]);
  const [currentExpandedIndex, setCurrentExpandedIndex] = useState<number>(-1);
  const [isPopoverOpen, setIsPopoverOpen] = useState<boolean>(false);

  useEffect(() => {
    trackOptimizerEvents(SAVE_GATEWAY_PAGE_VISIT);
    fetchSupportedGateways()
      .then((res) => {
        if (res?.success) {
          setSupportedGateways(res?.data);
        }
      })
      .finally(() => {
        setLoadingSupportedGateways(false);
      });
  }, []);

  useEffect(() => {
    if (supportedGateways) {
      const supportedGatewaysList: { label: string; value: string }[] =
        filterPopularGateways(supportedGateways);
      setSupportedGatewaysList(supportedGatewaysList);
    }
  }, [supportedGateways]);

  const handleBack = () => {
    trackOptimizerEvents(SAVE_GATEWAY_BACK_CLICK);
    prevStep();
  };

  const handleAccordionExpand = ({ expandedIndex }) => {
    setCurrentExpandedIndex(expandedIndex);
    setIsPopoverOpen(true);
  };

  const handleChange = (e, gateway) => {
    const { name, value } = e;
    setGatewayDetails({
      ...gatewayDetails,
      [gateway]: { ...gatewayDetails[gateway], [name]: value },
    });
  };

  const saveCredentials = (gateway) => {
    trackOptimizerEvents(saveGatewayCredsClick({ gateway }));
    setIsSaving(true);
    const integrationAuditFlow =
      isIntegrationAuditEnabled(splitz) && isGatewaySupportIntegrationAudit(gateway);
    let payload: Record<string, unknown> = {
      Gateway: gateway,
      Description: `Onboarding ${gateway}`,
      Provider_name: `${gateway} onboarding`,
      Gateway_details: {
        ...gatewayDetails?.[gateway],
        'Payment Methods': [],
      },
    };
    if (gateway === 'paytm') {
      payload.Gateway_details = {
        ...(payload.Gateway_details as Object),
        WEBSITE: 'DEFAULT', // this value remains same for all paytm providers
      };
    }
    if ((supportedGateways[gateway] as Record<string, unknown>)?.optimizer_seamless_disabled) {
      payload.Gateway_details = {
        ...(payload.Gateway_details as Object),
        optimizer_seamless_disabled: true,
      };
    }

    if (integrationAuditFlow) {
      addProviderV3({ payload })
        .then((res) => {
          if (res.success) {
            setCurrentExpandedIndex(-1);
            setSavedGateway([...savedGateway, gateway]);
          }
        })
        .catch(({ errors }) =>
          showNotification({
            type: 'error',
            message: errors[0],
          }),
        )
        .finally(() => setIsSaving(false));
    } else {
      (payload.Gateway_details as Object)['Payment Methods'] =
        supportedGateways[gateway]?.['Payment Methods']?.data_value?.filter(
          (method) => method !== 'wallet',
        ) || [];
      if ((supportedGateways[gateway] as Record<string, unknown>)?.TPV) {
        payload = handleTPVFeatures(payload);
      }

      addProvider({ payload })
        .then((res) => {
          if (res.success) {
            setCurrentExpandedIndex(-1);
            setSavedGateway([...savedGateway, gateway]);
          }
        })
        .catch(({ errors }) =>
          showNotification({
            type: 'error',
            message: errors[0],
          }),
        )
        .finally(() => setIsSaving(false));
    }
  };

  const submitDetails = () => {
    const data = selectedGateways.map((gateway) => ({
      gateway,
      saved: savedGateway.includes(gateway),
    }));
    trackOptimizerEvents(submitDetailsClick({ gateways: data }));
    // go to next page
    prevStep(true);
  };

  return (
    <>
      <SaveGatewayDiv>
        <Box display="flex" flexDirection="column" gap="spacing.8">
          <Box display="flex" flexDirection="column" gap="spacing.5">
            <Box>
              <IconButton
                icon={() => <ArrowLeftIcon size="medium" color="surface.icon.gray.normal" />}
                accessibilityLabel="Back"
                onClick={handleBack}
              />
            </Box>
            <Box display="flex" justifyContent="space-between" alignItems="center">
              <Heading size="large" color="surface.text.gray.normal">
                Share payment gateway details
              </Heading>
              <Link
                variant="anchor"
                icon={ExternalLinkIcon}
                iconPosition="right"
                color="primary"
                size="medium"
                href={SAVE_GATEWAY_DOC_LINK}
                target="_blank"
                rel="noopener noreferrer"
              >
                Documentation
              </Link>
            </Box>
          </Box>
          {loadingSupportedGateways ? (
            <Box>
              <Spinner size="xlarge" accessibilityLabel="loading" />
            </Box>
          ) : (
            <Box>
              <Dropdown selectionType="multiple" _width="55%">
                <SelectInput
                  label="Select payment gateways you use currently"
                  placeholder="Select a gateway"
                  name="gateways"
                  icon={SearchIcon}
                  labelPosition="top"
                  onChange={({ values }) => setSelectedGateways(values)}
                />
                <DropdownOverlay>
                  <ActionList>
                    <ActionListSection title="Popular gateway">
                      {POPULAR_GATEWAYS?.map((popularGateway) => (
                        <ActionListItem
                          title={popularGateway.label}
                          value={popularGateway.value}
                          key={popularGateway.value}
                        />
                      ))}
                    </ActionListSection>
                    <ActionListSection title={`Others (${supportedGatewaysList?.length})`}>
                      {supportedGatewaysList?.map((supportedGateway) => (
                        <ActionListItem
                          title={supportedGateway.label}
                          value={supportedGateway.value}
                          key={supportedGateway.value}
                        />
                      ))}
                    </ActionListSection>
                  </ActionList>
                </DropdownOverlay>
              </Dropdown>
            </Box>
          )}
          {selectedGateways?.length > 0 && (
            <GatewayCreds
              currentExpandedIndex={currentExpandedIndex}
              handleAccordionExpand={handleAccordionExpand}
              selectedGateways={selectedGateways}
              savedGateway={savedGateway}
              supportedGateways={supportedGateways}
              isPopoverOpen={isPopoverOpen}
              setIsPopoverOpen={setIsPopoverOpen}
              handleChange={handleChange}
              saveCredentials={saveCredentials}
              isSaving={isSaving}
              gatewayDetails={gatewayDetails}
            />
          )}
        </Box>
      </SaveGatewayDiv>
      <SaveGatewayFooter>
        <Box display="flex" padding="spacing.7" flexDirection="row-reverse">
          <Button
            icon={ArrowRightIcon}
            iconPosition="right"
            size="large"
            onClick={submitDetails}
            isDisabled={savedGateway.length <= 0}
            marginRight="248px"
          >
            Submit details
          </Button>
        </Box>
      </SaveGatewayFooter>
    </>
  );
};
