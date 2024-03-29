import React from 'react';
import {
  Box,
  Card,
  CardBody,
  ExternalLinkIcon,
  Heading,
  Link,
  Text,
  Theme,
} from '@razorpay/blade/components';
import styled from 'styled-components';

import DefaultTransparentUserImage from 'assets/rize/marketplace/default-transparent-user.png';
import {
  resolveToRizeUrl,
  useMarketplaceProduct,
} from 'merchant/views/RizeMarketplace/common/utils';

import { CompanyDetailsProps } from './types';

const Avatar = styled.img(
  ({ theme }: { theme: Theme }) => `
  width: ${theme.spacing[10]}px;
  height: ${theme.spacing[10]}px;
  border-radius: ${theme.border.radius.max}px;
  object-fit: cover;
  flex-shrink: 0;
`,
);

const CompanyDetails = ({ slug }: CompanyDetailsProps): JSX.Element | null => {
  const { data: product } = useMarketplaceProduct(slug);
  if (!product?.data?.company) return null;

  const { company } = product.data;

  return (
    <Box display="flex" flexDirection="column" gap="spacing.7">
      <Box>
        <Box
          display="flex"
          justifyContent="space-between"
          alignItems="center"
          marginBottom="spacing.3"
        >
          <Text color="surface.text.gray.subtle" size="large">
            About the company
          </Text>
          <Link
            href={company.website_url}
            target="_blank"
            icon={ExternalLinkIcon}
            size="large"
            iconPosition="right"
          >
            Visit Website
          </Link>
        </Box>

        <Heading as="span" size="large">
          {company.name}
        </Heading>
      </Box>
      {company.co_founders.length > 0 ? (
        <Box>
          <Text color="surface.text.gray.subtle" size="large">
            Team
          </Text>

          <Box display="flex" flexDirection="column" gap="spacing.3" marginTop="spacing.3">
            {company.co_founders.map(
              (cofounder): JSX.Element => (
                <Card
                  href={resolveToRizeUrl(`/profile/${cofounder.username}`)}
                  target="_blank"
                  key={cofounder.user_id}
                  padding="spacing.0"
                  backgroundColor="surface.background.gray.intense"
                  elevation="lowRaised"
                  testID="cofounder-card"
                >
                  <CardBody>
                    <Box display="flex" alignItems="center" gap="spacing.5" padding="spacing.5">
                      <Avatar
                        src={
                          cofounder.profile_picture
                            ? resolveToRizeUrl(cofounder.profile_picture)
                            : DefaultTransparentUserImage
                        }
                        alt={`Profile picture of ${cofounder.name}`}
                      />
                      <Text as="span" weight="semibold" size="large">
                        {cofounder.name}
                      </Text>
                    </Box>
                  </CardBody>
                </Card>
              ),
            )}
          </Box>
        </Box>
      ) : null}
    </Box>
  );
};

export default CompanyDetails;
