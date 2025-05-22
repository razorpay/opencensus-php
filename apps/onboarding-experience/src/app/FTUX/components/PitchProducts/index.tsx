import React from 'react';
import { Box, Heading, Text, IconComponent } from '@razorpay/blade/components';
import ImagePostCard from '@OnboardingExperienceCommons/components/ImagePostCard';

interface Product {
  tagIcon: IconComponent;
  tagText: string;
  title: string;
  description: string;
  linkIcon: IconComponent;
  linkText: string;
  handleClick: () => void;
  image: string;
}

interface PitchProductsProps {
  title: string;
  subtitle: string;
  products: Product[];
}

const PitchProducts = ({ title, subtitle, products }: PitchProductsProps) => {
  return (
    <Box>
      <Box paddingLeft={{ base: 'spacing.5', m: 'spacing.7' }}>
        <Heading weight="semibold" size="medium">
          {title}
        </Heading>
        <Text size="medium" color="surface.text.gray.subtle" marginTop="spacing.3">
          {subtitle}
        </Text>
      </Box>
      <Box
        display="flex"
        flexDirection={{
          base: 'column',
          l: 'row',
        }}
        gap="spacing.7"
        marginTop="spacing.7"
        justifyContent={{ base: 'none', l: 'space-between' }}
      >
        {products.map((product: Product, index: number) => (
          <Box maxWidth={{ base: 'auto', l: '280px' }} width="100%" key={index}>
            <ImagePostCard
              tagIcon={product.tagIcon}
              tagText={product.tagText}
              title={product.title}
              description={product.description}
              linkIcon={product.linkIcon}
              linkText={product.linkText}
              handleClick={product.handleClick}
              image={product.image}
            />
          </Box>
        ))}
      </Box>
    </Box>
  );
};

export default PitchProducts;
