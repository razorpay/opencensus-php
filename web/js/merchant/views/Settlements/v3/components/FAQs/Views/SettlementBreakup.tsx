import { Link, Text } from '@razorpay/blade/components';
import { FaqInterface } from 'merchant/views/Settlements/v3/typings';
import React from 'react';
import { StyledFaqContent, TextLink, VideoPlayer } from './styled';

const SettlementBreakup = ({ isMobile }: FaqInterface): JSX.Element => {
  return (
    <StyledFaqContent>
      {!isMobile && (
        <Text weight="semibold" marginBottom="spacing.2">
          Why have I received less money in my account?
        </Text>
      )}
      <Text color="surface.text.gray.subtle">
        Your final settlement amount will vary after adjusting for platform fees, taxes, refunds,
        credits, or any other charges.
      </Text>
      <TextLink>
        <Text marginRight="spacing.2" color="surface.text.gray.subtle">
          To know more about it, refer to the video below or check our
        </Text>
        <Link
          href="https://razorpay.com/docs/payments/settlements/dashboard/"
          target="_blank"
          rel="noreferrer noopener"
        >
          Settlements break-up guide
        </Link>
      </TextLink>
      <VideoPlayer
        src="https://www.youtube.com/embed/fsblhQ1uhoE"
        allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"
        frameborder="0"
        allowfullscreen
      />
    </StyledFaqContent>
  );
};

export default SettlementBreakup;
