import React, { useEffect, useState } from 'react';
import { Box, Button, Heading, Text, ArrowRightIcon } from '@razorpay/blade/components';
import moment from 'moment';

import { useMobile } from 'common/hooks/useMobile';
import useModalComponents from 'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/v2/hooks/useModalComponents';

import { analyticsTrack } from 'common/utils/analytics';

import { useGetTncUpdate } from 'merchant/components/TncUpdateModal/hooks/useGetTncUpdateDetails';
import { useSaveTncUpdateAcceptance } from 'merchant/components/TncUpdateModal/hooks/useSaveTncUpdateAcceptance';

import {
  SaveTncUpdateAnalyticsArguments,
  TncUpdateApiData,
} from 'merchant/components/TncUpdateModal/types';

import TncIcon from 'assets/tnc.svg';
import { TermsLink } from './styled-components';

const TncUpdateModal = () => {
  const isMobile = useMobile();
  const { Modal, ModalBody, ModalFooter } = useModalComponents(isMobile);

  const [isOpen, setIsOpen] = useState(false);

  const {
    data: tncUpdateData,
    isLoading: isTncUpdateLoading,
    isError: isTncUpdateFetchError,
  } = useGetTncUpdate();

  useEffect(() => {
    if (!isTncUpdateLoading && !isTncUpdateFetchError && tncUpdateData) {
      setIsOpen(tncUpdateData.show_tnc);

      if (tncUpdateData.show_tnc) {
        analyticsTrack({
          objectName: 'Razorpay Terms and Conditions',
          actionName: 'displayed',
          screen: 'tnc update modal',
          properties: {
            version: tncUpdateData?.version,
          },
        });
      }
    }
  }, [isTncUpdateLoading, isTncUpdateFetchError, tncUpdateData]);

  const onDismiss = () => {
    setIsOpen(false);
  };

  const triggerSaveTncUpdateAnalytics = (additionalProperties: SaveTncUpdateAnalyticsArguments) => {
    analyticsTrack({
      objectName: 'Razorpay Terms and Conditions',
      actionName: 'closed',
      screen: 'tnc update modal',
      properties: {
        version: tncUpdateData?.version,
        ...additionalProperties,
      },
    });
  };

  const { mutate: saveTncUpdateAcceptance, isLoading } = useSaveTncUpdateAcceptance(
    triggerSaveTncUpdateAnalytics,
  );

  const onAccept = () => {
    analyticsTrack({
      objectName: 'Razorpay T&C Okay Got it',
      actionName: 'clicked',
      screen: 'tnc update modal',
      properties: {
        version: tncUpdateData?.version,
      },
    });

    saveTncUpdateAcceptance({
      accepted_version: (tncUpdateData as TncUpdateApiData)?.version,
      accepted_at: moment().unix(),
    }).finally(() => {
      onDismiss();
    });
  };

  return (
    <Modal isOpen={isOpen} onDismiss={onDismiss} size="small" snapPoints={[0.65, 0.75, 0.9]}>
      <ModalBody>
        <Box display="flex" flexDirection="column" gap={isMobile ? 'spacing.5' : 'spacing.3'}>
          <Box width="88px" height="80px">
            <img src={TncIcon} alt="tnc-icon" />
          </Box>
          <Box>
            <Heading as="h3" size="small">
              Razorpay Terms and Conditions
            </Heading>
            <Text as="span" size="medium">
              To proceed, please review our updated{' '}
              <TermsLink
                href="https://razorpay.com/terms/"
                rel="noreferrer noopener"
                target="_blank"
              >
                Terms and Conditions
              </TermsLink>
              . It is important that you read and understand them before continuing to use our
              services.
            </Text>
          </Box>
        </Box>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" justifyContent="flex-end" alignItems="center">
          <Button
            variant="primary"
            color="primary"
            size="medium"
            isFullWidth={isMobile ? true : false}
            icon={ArrowRightIcon}
            iconPosition="right"
            onClick={onAccept}
            isLoading={isLoading}
          >
            Okay, Got it
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};

export default TncUpdateModal;
