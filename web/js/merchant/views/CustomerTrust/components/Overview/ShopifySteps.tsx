import React, { useState } from 'react';
import styled from 'styled-components';
import {
  ArrowUpRightIcon,
  StepItem,
  StepGroup,
  Button,
  Text,
  Box,
  Heading,
  Modal,
  ModalBody,
} from '@razorpay/blade/components';
import VIDEO_THUMBNAIL from './thumbnail.png';
import { BUYER_PROTECT_SHOPIFY_INTEGRATION_GUIDE } from '../../constants';

const MarkerContainer = styled.div`
  height: 20px;
  width: 20px;
  border-radius: 50%;
  background-color: rgba(108, 132, 157, 0.09);
  display: flex;
  align-items: center;
  justify-content: center;
`;

const Marker = ({ number }: { number: number }) => {
  return (
    <MarkerContainer>
      <Text size="xsmall" weight="medium" color="surface.text.gray.normal">
        {number}
      </Text>
    </MarkerContainer>
  );
};

export const ShopifySteps = () => {
  const [isVideoModalOpen, setIsVideoModalOpen] = useState(false);

  return (
    <>
      <Modal size="large" isOpen={isVideoModalOpen} onDismiss={() => setIsVideoModalOpen(false)}>
        <ModalBody>
          <Box width="100%" height="555px">
            <iframe
              width="100%"
              height="100%"
              src="https://www.youtube.com/embed/Lyangg3UhXg?si=4BTao3ELAjppJw4m"
              title="YouTube video player"
              frameBorder="0"
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
              referrerPolicy="strict-origin-when-cross-origin"
              allowFullScreen
            />
          </Box>
        </ModalBody>
      </Modal>

      <Box
        display="flex"
        flexDirection={{
          base: 'column',
          m: 'row',
        }}
        justifyContent="space-between"
        alignItems={{
          base: 'stretch',
          m: 'flex-start',
        }}
        gap="spacing.7"
        paddingY="spacing.7"
      >
        <Box
          display="flex"
          flexDirection="column"
          gap="spacing.7"
          width={{
            base: '100%',
            m: '590px',
          }}
        >
          <Box display="flex" flexDirection="column">
            <Text weight="semibold" color="surface.text.gray.normal">
              Complete setup for Product Page
            </Text>
            <Text size="small" color="surface.text.gray.muted">
              Follow the steps below to display Buyer Protection widget on your product page
            </Text>
          </Box>

          <StepGroup orientation="vertical" size="medium">
            <StepItem
              title="Install the App from Shopify Appstore"
              description='Click "Start Setup" to install the app on Shopify.'
              stepProgress="none"
              marker={<Marker number={1} />}
            />
            <StepItem
              title="Enable Buyer Protection"
              description="Open the app, enter your Razorpay Live API Key ID, and click Save to activate the feature."
              stepProgress="none"
              marker={<Marker number={2} />}
            />
            <StepItem
              title="Add the Buyer Protection Widget"
              description="After saving your API Key, you'll be redirected to the Product Configuration page. Under Template, hover below Price, click Add Block, and select Buyer Protection."
              stepProgress="none"
              marker={<Marker number={3} />}
            />
          </StepGroup>

          <Box>
            <Button
              variant="primary"
              icon={ArrowUpRightIcon}
              iconPosition="right"
              onClick={() => {
                window.open(BUYER_PROTECT_SHOPIFY_INTEGRATION_GUIDE, '_blank');
              }}
            >
              Start set up
            </Button>
          </Box>
        </Box>

        <Box
          display="flex"
          flexDirection="column"
          borderColor="surface.border.gray.normal"
          borderWidth="thin"
          borderStyle="solid"
          borderRadius="medium"
          flex="1"
          marginTop={{
            base: 'spacing.4',
            m: 'spacing.0',
          }}
        >
          <div
            onClick={() => setIsVideoModalOpen(true)}
            style={{
              cursor: 'pointer',
              width: '100%',
            }}
          >
            <img
              src={VIDEO_THUMBNAIL}
              alt="Video Thumbnail"
              width="100%"
              height="auto"
              style={{ maxHeight: '230px', objectFit: 'cover' }}
            />
          </div>
          <Box
            display="flex"
            flexDirection="column"
            gap="spacing.4"
            justifyContent="center"
            padding="spacing.7"
          >
            <Box display="flex" flexDirection="column" gap="spacing.4">
              <Heading size="small" weight="semibold" color="surface.text.gray.normal">
                How to setup Buyer Protection on your Product Page?
              </Heading>

              <Text color="surface.text.gray.muted">
                Give your customers peace of mind by enabling Buyer Protection. This quick setup
                guide walks you through adding it to your product pages — no coding required.
              </Text>
            </Box>
          </Box>
        </Box>
      </Box>
    </>
  );
};
