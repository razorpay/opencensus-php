import React from 'react';
import {
  Box,
  Button,
  Card,
  CardBody,
  Skeleton,
  Text,
  Theme,
  Heading,
  useTheme,
} from '@razorpay/blade/components';
import { useBreakpoint } from '@razorpay/blade/utils';
import { useHref, useLinkClickHandler } from 'react-router-dom';
import styled from 'styled-components';

import DealBox from 'merchant/views/RizeMarketplace/common/components/DealBox';
import { resolveToRizeUrl } from 'merchant/views/RizeMarketplace/common/utils';

import { ProductCardTileProps } from './types';

const ProductLogo = styled.img(
  ({ theme }: { theme: Theme }) => `
  border-radius: ${theme.border.radius.medium}px;
  border: ${theme.border.width.thin}px solid ${theme.colors.surface.border.gray.muted};
  background-color: ${theme.colors.interactive.icon.staticWhite.normal};
  width: 36px;
  height: 36px;
  object-fit: cover;
  flex-shrink: 0;

  @media (min-width: ${theme.breakpoints.l}px) {
    width: 74px;
    height: 74px;
  }
`,
);

const StyledDealBox = styled(DealBox)(
  ({ theme }: { theme: Theme }) => `
  order: -1;

  @media (min-width: ${theme.breakpoints.l}px) {
    order: 1;
  }
`,
);

const ProductCardTile = ({
  name,
  logoSrc,
  slug,
  category,
  excerpt,
  offer,
  target,
  showKnowMoreCTA,
  truncateExcerpt = 3,
  onClick,
}: ProductCardTileProps): JSX.Element => {
  const to = `/rize-marketplace/${slug}`;
  const href = useHref(to);
  const handleClick = useLinkClickHandler<HTMLElement>(to, { target });
  const { theme } = useTheme();
  const { matchedBreakpoint } = useBreakpoint(theme);
  const isL = matchedBreakpoint === 'l' || matchedBreakpoint === 'xl';

  const knowMoreButton = (
    <Button
      href={href}
      target={target}
      size={isL ? 'large' : 'medium'}
      onClick={(event) => {
        handleClick(event);
        onClick?.();
      }}
    >
      Know more
    </Button>
  );

  return (
    <Card
      padding="spacing.0"
      href={href}
      target={target}
      accessibilityLabel={`Open ${name}`}
      elevation="midRaised"
      shouldScaleOnHover
      // `any` can be removed once we upgrade to a Blade version that includes this patch https://github.com/razorpay/blade/issues/1992 (v10.23.5 and above)
      onClick={
        ((event) => {
          handleClick(event);
          onClick?.();
        }) as any
      }
      height="100%"
      testID="product-card-tile"
    >
      <CardBody height="100%">
        <Box
          borderWidth="thin"
          borderColor="surface.border.gray.muted"
          borderRadius="medium"
          padding={['spacing.7', 'spacing.6']}
          minWidth={{ base: '270px', s: '332px' }}
          height="100%"
        >
          <Box
            display="flex"
            flexDirection={{ base: 'column', l: 'row' }}
            gap={{ base: 'spacing.4', l: 'spacing.5' }}
            alignItems={{ l: 'center' }}
          >
            <ProductLogo src={resolveToRizeUrl(logoSrc)} alt={`${name} logo`} />
            <Box>
              <Heading as="h3" size="large" color="surface.text.gray.normal">
                {name}
              </Heading>
              <Text
                variant="body"
                size="small"
                weight="semibold"
                marginTop={{ base: 'spacing.4', l: 'spacing.3' }}
                color="surface.text.gray.muted"
              >
                {category.toUpperCase()}
              </Text>
            </Box>
            {showKnowMoreCTA && isL ? <Box marginLeft="auto">{knowMoreButton}</Box> : null}
          </Box>
          <Box
            display="grid"
            gridTemplateColumns={{ base: '1fr', l: '1fr auto' }}
            marginTop={{ base: 'spacing.4', l: 'spacing.7' }}
            gap="spacing.5"
            alignItems="center"
          >
            <Text
              size="large"
              color="surface.text.gray.normal"
              truncateAfterLines={truncateExcerpt}
            >
              {excerpt}
            </Text>

            <StyledDealBox>{offer}</StyledDealBox>
          </Box>
          {showKnowMoreCTA && !isL ? <Box marginTop="spacing.5">{knowMoreButton}</Box> : null}
        </Box>
      </CardBody>
    </Card>
  );
};

const ProductCardTileSkeleton = (): JSX.Element => (
  <Card padding="spacing.0" elevation="midRaised" testID="product-card-tile-skeleton">
    <CardBody>
      <Box
        borderWidth="thin"
        borderColor="surface.border.gray.muted"
        borderRadius="medium"
        padding={['spacing.7', 'spacing.6']}
        minWidth={{ base: '270px', s: '332px' }}
      >
        <Box
          display="flex"
          flexDirection={{ base: 'column', l: 'row' }}
          gap={{ base: 'spacing.4', l: 'spacing.5' }}
          alignItems={{ l: 'center' }}
        >
          <Skeleton
            width={{ base: '36px', l: '74px' }}
            height={{ base: '36px', l: '74px' }}
            borderRadius="medium"
          />
          <Box>
            <Skeleton height="spacing.5" width="96px" borderRadius="medium" />
            <Skeleton
              height="spacing.5"
              width="spacing.8"
              marginTop={{ base: 'spacing.4', l: 'spacing.3' }}
              borderRadius="medium"
            />
          </Box>
        </Box>
        <Box
          display="grid"
          gridTemplateColumns={{ base: '1fr', l: '1fr auto' }}
          marginTop={{ base: 'spacing.4', l: 'spacing.7' }}
          gap="spacing.3"
          alignItems="center"
        >
          <Skeleton height="spacing.4" width="100%" borderRadius="medium" />
          <Skeleton height="spacing.4" width="100%" borderRadius="medium" />
          <Skeleton height="spacing.4" width="100%" borderRadius="medium" />
        </Box>
      </Box>
    </CardBody>
  </Card>
);

export { ProductCardTile, ProductCardTileSkeleton };
