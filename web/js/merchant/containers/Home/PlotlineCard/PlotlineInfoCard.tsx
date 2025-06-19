import { ArrowRightIcon, Box, Card, Link, Text } from '@razorpay/blade/components';
import { isMobileDevice } from '@libs/shared-utils';
import React from 'react';
import styled from 'styled-components';

interface PlotlineInfoCardProps {
  icon: React.ReactNode;
  title: string;
  showCta?: boolean;
  ctaText?: string;
  ctaOnClick?: () => void;
  backgroundAsset?: {
    desktop: string;
    mobile: string;
  };
}

const Image = styled.img`
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 1;
  object-fit: cover;
`;

const PlotlineInfoCard = ({
  icon,
  title,
  ctaText,
  ctaOnClick = () => {},
  backgroundAsset,
}: PlotlineInfoCardProps) => {
  return (
    <Card
      margin={{ m: ['spacing.6', 'spacing.6', 'spacing.0', 'spacing.6'], base: 'spacing.0' }}
      position="relative"
    >
      <Box
        display="flex"
        alignItems="center"
        flexDirection={{ m: 'row', base: 'column' }}
        justifyContent={{ m: 'flex-start', base: 'center' }}
        gap="spacing.3"
        zIndex={2}
      >
        {icon}
        <Box>
          <Text color="surface.text.gray.normal" textAlign="center">
            {title}
          </Text>
        </Box>
        {!!ctaText && (
          <Box>
            <Link icon={ArrowRightIcon} iconPosition="right" onClick={ctaOnClick}>
              {ctaText}
            </Link>
          </Box>
        )}
      </Box>
      {backgroundAsset && (
        <Image
          src={isMobileDevice() ? backgroundAsset.mobile : backgroundAsset.desktop}
          alt="Rewards Banner"
        />
      )}
    </Card>
  );
};

export default PlotlineInfoCard;
