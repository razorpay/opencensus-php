import React from 'react';
import {
  Box,
  Button,
  Accordion,
  AccordionItem,
  AccordionItemHeader,
  AccordionItemBody,
  TextInput,
  Badge,
  CheckIcon,
  Popover,
} from '@razorpay/blade/components';

import { titleCase } from 'common/utils/rzp-utils';
import {
  SKIP_INPUT_FOR_PROVIDER_KEYS,
  FIND_DETAILS_SUPPORTED_ON_GATEWAYS,
} from 'merchant/views/Optimizer/OnBoarding/constants';
import { gatewayLogos } from 'merchant/views/Optimizer/utils';
import { FindDetails } from './FindDetails';

export const GatewayCreds = ({
  currentExpandedIndex,
  handleAccordionExpand,
  selectedGateways,
  savedGateway,
  supportedGateways,
  isPopoverOpen,
  setIsPopoverOpen,
  handleChange,
  saveCredentials,
  isSaving,
  gatewayDetails,
}: {
  currentExpandedIndex: number;
  handleAccordionExpand: ({ expandedIndex }: { expandedIndex: number }) => void;
  selectedGateways: string[];
  savedGateway: string[];
  supportedGateways: Record<string, unknown>;
  isPopoverOpen: boolean;
  setIsPopoverOpen: (isOpen: boolean) => void;
  handleChange: (e: any, gateway: string) => void;
  saveCredentials: (gateway: string) => void;
  isSaving: boolean;
  gatewayDetails: Record<string, Record<string, string>>;
}): JSX.Element => {
  const allDetailsPresent = (gateway: string) => {
    let isValid = true;
    Object.keys(supportedGateways[gateway] as object).map((key) => {
      if (!SKIP_INPUT_FOR_PROVIDER_KEYS.includes(key)) {
        if (!gatewayDetails?.[gateway]?.[key]) {
          isValid = false;
        }
      }
    });
    return isValid;
  };

  return (
    <Box maxHeight="38vh" overflowY="scroll">
      <Accordion
        variant="filled"
        expandedIndex={currentExpandedIndex}
        onExpandChange={handleAccordionExpand}
      >
        {selectedGateways.map((gateway, index) => (
          <AccordionItem key={gateway} isDisabled={savedGateway.includes(gateway)}>
            <AccordionItemHeader
              title={supportedGateways[gateway]?.['Gateway Name']?.data_value || ''}
              leading={<img src={gatewayLogos[gateway]} alt={gateway} height="24px" width="24px" />}
              trailing={
                savedGateway.includes(gateway) ? (
                  <Badge color="positive" size="large" icon={CheckIcon}>
                    Credentials saved
                  </Badge>
                ) : null
              }
            />
            <AccordionItemBody>
              <Popover
                content={<FindDetails gateway={gateway} />}
                placement="right-start"
                title={`Where to find ${supportedGateways[gateway]?.['Gateway Name']?.data_value} credentials?`}
                isOpen={
                  isPopoverOpen &&
                  currentExpandedIndex === index &&
                  FIND_DETAILS_SUPPORTED_ON_GATEWAYS.includes(gateway)
                }
                onOpenChange={({ isOpen }) => setIsPopoverOpen(isOpen)}
              >
                <Box>
                  <Box display="grid" gap="spacing.7" gridTemplateColumns="48% 48%">
                    {Object.keys(supportedGateways[gateway] as Object).map((key) =>
                      !SKIP_INPUT_FOR_PROVIDER_KEYS.includes(key) ? (
                        <TextInput
                          name={key}
                          placeholder={`Enter ${key}`}
                          label={titleCase(key)}
                          labelPosition="top"
                          type="text"
                          onChange={(e) => handleChange(e, gateway)}
                        />
                      ) : null,
                    )}
                  </Box>
                  <Box textAlign="right" marginTop="spacing.3">
                    <Button
                      variant="secondary"
                      type="button"
                      onClick={() => saveCredentials(gateway)}
                      isLoading={isSaving}
                      isDisabled={!allDetailsPresent(gateway) || savedGateway.includes(gateway)}
                    >
                      Save credentials
                    </Button>
                  </Box>
                </Box>
              </Popover>
            </AccordionItemBody>
          </AccordionItem>
        ))}
      </Accordion>
    </Box>
  );
};
