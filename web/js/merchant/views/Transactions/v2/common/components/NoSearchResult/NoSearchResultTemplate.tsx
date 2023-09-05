import React from 'react';
import { Box, Text, Link, ArrowRightIcon } from '@razorpay/blade/components';
import { Image } from './styled';
import { NoSearchResultTemplateProps } from './types';

const NoSearchResultTemplate = ({ page, config }: NoSearchResultTemplateProps): JSX.Element => {
  const {
    image: { src, alt },
    title,
    subtitle,
    link: { href, linkText } = {},
  } = config(page);
  return (
    <Box
      maxWidth="440px"
      margin="auto"
      marginTop={{
        base: '120px',
        l: '150px',
      }}
      marginBottom={{
        base: '282px',
        l: '270px',
      }}
    >
      <Box display="flex" flexDirection="column" justifyContent="center" alignItems="center">
        <Image src={src} alt={alt} />
        <Text textAlign="center" weight="bold" size="large" marginTop="spacing.4">
          {title}
        </Text>
        <Text textAlign="center">{subtitle}</Text>
        {href && (
          <Link
            href={href}
            icon={ArrowRightIcon}
            iconPosition="right"
            rel="noreferrer noopener"
            target="_blank"
            variant="anchor"
            marginTop="spacing.7"
          >
            {linkText}
          </Link>
        )}
      </Box>
    </Box>
  );
};

export default NoSearchResultTemplate;
