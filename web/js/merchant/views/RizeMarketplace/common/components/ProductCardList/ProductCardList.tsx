import React from 'react';
import { Box, Card, CardBody, Text, Theme, Title } from '@razorpay/blade/components';
import { useHref, useLinkClickHandler } from 'react-router-dom';
import styled from 'styled-components';

import DealBox from 'merchant/views/RizeMarketplace/common/components/DealBox';
import { resolveToRizeUrl } from 'merchant/views/RizeMarketplace/common/utils';

import { ProductCardListProps } from './types';

const ProductLogo = styled.img(
  ({ theme }: { theme: Theme }) => `
  border-radius: ${theme.border.radius.medium}px;
  border: ${theme.border.width.thin}px solid ${theme.colors.surface.border.normal.lowContrast};
  background-color: ${theme.colors.static.white};
  width: 36px;
  height: 36px;
  object-fit: cover;
  flex-shrink: 0;
`,
);

const ProductCardList = ({
  name,
  slug,
  logoSrc,
  category,
  excerpt,
  offer,
  target,
  onClick,
}: ProductCardListProps): JSX.Element => {
  const to = `/rize-marketplace/${slug}`;
  const href = useHref(to);
  const handleClick = useLinkClickHandler(to, { target });

  return (
    <Card
      padding="spacing.0"
      href={href}
      target={target}
      accessibilityLabel={`Open ${name}`}
      shouldScaleOnHover
      testID="product-card-list"
      // `any` can be removed once we upgrade to a Blade version that includes this patch https://github.com/razorpay/blade/issues/1992 (v10.23.5 and above)
      onClick={
        ((event) => {
          handleClick(event);
          onClick?.();
        }) as any
      }
      elevation="midRaised"
    >
      <CardBody>
        <Box
          borderWidth="thin"
          borderColor="surface.border.normal.lowContrast"
          borderRadius="medium"
          padding={['spacing.7', 'spacing.6']}
          minWidth={{ base: '270px', s: '332px' }}
        >
          <Box
            display="flex"
            flexDirection={{ base: 'column', l: 'row' }}
            justifyContent="space-between"
            gap={{ base: 'spacing.4', l: 'spacing.0' }}
          >
            <Box display="flex" flexDirection="column" alignItems="flex-start" gap="spacing.4">
              <Box
                display="flex"
                flexDirection={{ base: 'column', l: 'row' }}
                gap={{ base: 'spacing.4', l: 'spacing.5' }}
              >
                <ProductLogo src={resolveToRizeUrl(logoSrc)} alt={`${name} logo`} />
                <Title as="h3" size="small" type="normal">
                  {name}
                </Title>
              </Box>

              <Text
                variant="body"
                size="small"
                weight="bold"
                type="normal"
                color="surface.text.muted.lowContrast"
              >
                {category.toUpperCase()}
              </Text>
            </Box>
            <DealBox>{offer}</DealBox>
          </Box>
          <Text
            size="large"
            type="normal"
            color="surface.text.normal.lowContrast"
            marginTop={{ base: 'spacing.5', l: 'spacing.7' }}
          >
            {excerpt}
          </Text>
        </Box>
      </CardBody>
    </Card>
  );
};

export default ProductCardList;
