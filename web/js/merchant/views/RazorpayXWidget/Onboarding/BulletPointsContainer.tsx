import React from 'react';
import { Box, CheckIcon, ChevronRightIcon, PlusIcon, Text } from '@razorpay/blade/components';
import { BulletPointsLeftContainer, BulletPointsRightContainer } from './styles';
import Image from 'common/ui/Image';

type widgetData = {
  cards: [
    {
      heading: string;
      subheading: string;
      text: string;
      footer: string;
      illustration: {
        url: string;
        alt_text: string;
        prefix: string;
        postfix: string;
      };
    },
    {
      heading: string;
      subheading: string;
      text: string;
    },
  ];
};

const BulletPoints = ({ text, variant }: { text: string; variant?: 'faded' }): JSX.Element => {
  const iconColor =
    variant === 'faded' ? 'surface.icon.gray.subtle' : 'interactive.icon.positive.normal';
  const IconComponent = variant === 'faded' ? ChevronRightIcon : CheckIcon;
  const textColor =
    variant === 'faded' ? 'surface.text.gray.subtle' : 'surface.text.staticWhite.normal';

  return (
    <Box display="flex" alignItems="flex-start" marginBottom="spacing.4">
      <IconComponent
        marginTop="spacing.2"
        marginRight="spacing.2"
        color={iconColor}
        size="medium"
      />
      <Box maxWidth="90%">
        <Text size="medium" color={textColor}>
          {text}
        </Text>
      </Box>
    </Box>
  );
};

export const BulletPointsContainer = ({ widgetData }: { widgetData: widgetData }): JSX.Element => {
  const rightColumnWidgetData = widgetData?.cards[1]?.text?.split(',');
  const leftColumnWidgetData = widgetData?.cards[0]?.text?.split(',');

  return (
    <Box marginTop="spacing.8" display="flex">
      <BulletPointsLeftContainer>
        <Box display="flex" alignItems="flex-end">
          <Text
            size="large"
            color="surface.text.staticWhite.normal"
            weight="semibold"
            marginRight="spacing.2"
          >
            {widgetData?.cards[0]?.illustration?.prefix}
          </Text>
          <Image
            height="26"
            src={widgetData?.cards[0]?.illustration?.url}
            alt={widgetData?.cards[0]?.illustration?.alt_text}
          />
          <Text
            size="large"
            color="surface.text.staticWhite.normal"
            weight="semibold"
            marginLeft="spacing.2"
          >
            {widgetData?.cards[0]?.illustration?.postfix}
          </Text>
        </Box>
        <Box marginTop="spacing.7">
          {leftColumnWidgetData.map((text) => (
            <BulletPoints key={text} text={text} />
          ))}
        </Box>
        <Text size="medium" color="surface.text.staticWhite.normal">
          {widgetData?.cards[0]?.footer}
        </Text>
      </BulletPointsLeftContainer>
      <PlusIcon
        marginX="spacing.3"
        color="interactive.icon.gray.normal"
        alignSelf="center"
        size="2xlarge"
      />
      <BulletPointsRightContainer>
        <Box display="flex" alignItems="flex-start" flexDirection="column">
          <Text size="medium" color="surface.text.staticWhite.normal">
            {widgetData?.cards[1]?.heading}
          </Text>
          <Text size="medium" color="surface.text.gray.subtle">
            {widgetData?.cards[1]?.subheading}
          </Text>
        </Box>
        <Box marginTop="spacing.7">
          {rightColumnWidgetData.map((text) => (
            <BulletPoints variant="faded" key={text} text={text} />
          ))}
        </Box>
      </BulletPointsRightContainer>
    </Box>
  );
};
