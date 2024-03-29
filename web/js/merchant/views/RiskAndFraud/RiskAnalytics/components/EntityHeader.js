import React from 'react';
import { Box, Text, Heading } from '@razorpay/blade/components';

import { ENTITY_HEADER } from 'merchant/views/RiskAndFraud/RiskAnalytics/constants';
import { HeaderPopover } from 'merchant/views/RiskAndFraud/components';

const EntityHeader = (props) => {
  const { entity } = props;
  const {
    title,
    description,
    popoverText,
    popoverTitle,
    popoverContent,
    popoverImage,
    imageAlt,
    docLink,
  } = ENTITY_HEADER[entity];

  return (
    <Box>
      <Heading marginBottom="spacing.1" size="small">
        {title}
      </Heading>
      <Box display="flex" flexDirection="row">
        <Text size="large" marginRight="spacing.1" color="surface.text.gray.muted">
          {description}
        </Text>
        <Box width="fit-content" mr={3}>
          <HeaderPopover
            entity={entity}
            text={popoverText}
            title={popoverTitle}
            content={popoverContent}
            contentImage={popoverImage}
            imageAlt={imageAlt}
            docLink={docLink}
          />
        </Box>
      </Box>
    </Box>
  );
};

export default EntityHeader;
