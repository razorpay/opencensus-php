import React from 'react';
import { useNavigate } from 'react-router-dom';
import {
  Box,
  Text,
  Heading,
  Button,
  Link,
  ExternalLinkIcon,
  ArrowRightIcon,
} from '@razorpay/blade/components';
import styled from 'styled-components';

import { addFPVSupportToPath } from 'merchant/views/MagicCheckout/utils/Configuration';

import { NewOffering } from 'merchant/views/MagicCheckout/MagicDashboard/WhatsNew/types';

const ImageWrapper = styled.img`
  height: 220px;
  width: auto;
  border-radius: 10px;
`;

export const WhatsNewCard = ({
  item,
  isRCODEnabled,
}: {
  item: NewOffering;
  isRCODEnabled: boolean;
}) => {
  const navigate = useNavigate();

  return (
    <Box display="flex" alignItems="center" gap="33px" elevation="midRaised" borderRadius="xlarge">
      <ImageWrapper src={item.image} alt={item.title} />
      <Box padding="spacing.5" display="flex" flexDirection="column" gap="spacing.5">
        <Heading as="h3" size="large">
          {item.title}
        </Heading>
        <Text color="surface.text.gray.muted">{item.description}</Text>
        <Box display="flex" alignItems="center" gap="spacing.8">
          {item.ctaLink && (
            <Button
              variant="primary"
              color="primary"
              size="medium"
              iconPosition="right"
              icon={ArrowRightIcon}
              marginRight="spacing.5"
              onClick={() => navigate(addFPVSupportToPath(item.ctaLink))}
            >
              Check it out
            </Button>
          )}
          {item.externalLink && (
            <Link
              icon={ExternalLinkIcon}
              variant="anchor"
              color="primary"
              size="medium"
              iconPosition="left"
              href={item.externalLink.href}
              target="_blank"
              rel="noopener noreferer"
            >
              {item.externalLink.label}
            </Link>
          )}

          {item.docLink && (
            <Link
              icon={ExternalLinkIcon}
              variant="anchor"
              color="primary"
              size="medium"
              iconPosition="left"
              href={item.docLink(isRCODEnabled)}
              target="_blank"
              rel="noopener noreferer"
            >
              Documentation
            </Link>
          )}
        </Box>
      </Box>
    </Box>
  );
};
