import React from 'react';
import { Box, Heading, Text } from '@razorpay/blade/components';
import ImagePostCard, {
  ImagePostCardPropsType,
} from '@OnboardingExperienceCommons/components/ImagePostCard';

interface PitchProductsProps {
  title: string;
  subtitle?: string;
  products: ImagePostCardPropsType[];
}

const PitchProducts = ({ title, subtitle, products }: PitchProductsProps) => {
  return (
    <Box>
      <Box paddingLeft={{ base: 'spacing.5', m: 'spacing.7' }}>
        <Heading weight="semibold" size="medium">
          {title}
        </Heading>
        {!!subtitle && (
          <Text size="medium" color="surface.text.gray.subtle" marginTop="spacing.3">
            {subtitle}
          </Text>
        )}
      </Box>
      <Box
        display="flex"
        flexDirection={{
          base: 'column',
          l: 'row',
        }}
        gap={{ base: 'spacing.7', m: 'spacing.4' }}
        marginTop="spacing.7"
      >
        {products.map((product: ImagePostCardPropsType) => (
          <Box key={product.title} maxWidth={{ base: 'auto', l: '288px' }} width="100%">
            <ImagePostCard {...product} />
          </Box>
        ))}
      </Box>
    </Box>
  );
};

export default PitchProducts;
