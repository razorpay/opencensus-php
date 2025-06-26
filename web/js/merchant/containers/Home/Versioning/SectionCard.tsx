import React from 'react';
import { Box, Heading, Text } from '@razorpay/blade/components';
import { VersioningConfig, HeadingSegment } from './types';
import { versioningSVGIcons } from './data';
import { SectionCardWrapper } from './styled';
import { useDeviceType, deviceTypeToSectionHeadingSize } from './utils';

interface SectionCardProps {
  config: VersioningConfig;
  marginTop?: boolean;
  marginBottom?: boolean;
}

function SectionCard({ config: responsiveConfig, marginTop, marginBottom }: SectionCardProps) {
  const deviceType = useDeviceType({ shouldAddBigMobileSplit: true });
  const isMobile = deviceType === 'mobile' || deviceType === 'bigMobile';
  const config = isMobile ? responsiveConfig.mobile : responsiveConfig.desktop;

  const renderHeadingElement = (element: HeadingSegment, index: number) => {
    switch (element.type) {
      case 'text':
        return (
          <Heading
            key={index}
            as="span"
            size={deviceTypeToSectionHeadingSize[deviceType] as 'large' | 'xlarge' | '2xlarge'}
            weight="semibold"
            color="surface.text.gray.normal"
          >
            {element.content}
          </Heading>
        );

      case 'svg':
        const SvgComponent = versioningSVGIcons[element.content as keyof typeof versioningSVGIcons];
        if (SvgComponent) {
          return (
            <img
              key={index}
              src={SvgComponent}
              alt={element.content}
              width={element.imgSize?.width}
              height={element.imgSize?.height}
            />
          );
        }
        return null;

      case 'break':
        return <br key={index} />;

      default:
        return null;
    }
  };

  const renderHeading = () => {
    const elements: React.ReactNode[] = [];
    let currentLineElements: React.ReactNode[] = [];

    config.heading.forEach((element, index) => {
      if (element.type === 'break') {
        // Finish current line and start new one
        if (currentLineElements.length > 0) {
          elements.push(
            <Box
              key={`line-${elements.length}`}
              display="flex"
              alignItems="center"
              gap="spacing.3"
              marginLeft={element.marginLeft}
              marginRight={element.marginRight}
              paddingLeft={element.paddingLeft}
              paddingRight={element.paddingRight}
            >
              {currentLineElements}
            </Box>,
          );
          currentLineElements = [];
        }
      } else {
        currentLineElements.push(renderHeadingElement(element, index));
      }
    });

    // Add remaining elements if any
    if (currentLineElements.length > 0) {
      elements.push(
        <Box key={`line-${elements.length}`} display="flex" alignItems="center" gap="spacing.3">
          {currentLineElements}
        </Box>,
      );
    }

    return elements;
  };

  return (
    <SectionCardWrapper
      backgroundColor={config.backgroundColor}
      marginTop={marginTop}
      marginBottom={marginBottom}
      isMobile={isMobile}
    >
      {/* Conditional Logo Section */}
      {config.logo?.src &&
        (typeof config.logo.src === 'string' ? (
          <img
            src={config.logo.src}
            alt="Razorpay Logo"
            width={config.logo.size?.width}
            height={config.logo.size?.height}
          />
        ) : (
          <config.logo.src />
        ))}

      {/* Heading Section */}
      <Box
        display="flex"
        flexDirection="column"
        gap={isMobile ? 'spacing.2' : 'spacing.4'}
        marginTop={config.logo ? (isMobile ? 'spacing.5' : 'spacing.8') : 'spacing.0'}
      >
        {renderHeading()}
      </Box>

      {/* Body Section */}
      <Box width={isMobile ? '100%' : '60%'}>
        <Text
          size={config.body.size}
          weight="semibold"
          color="surface.text.staticBlack.muted"
          textAlign="center"
          marginTop="spacing.6"
        >
          {config.body.text}
        </Text>
      </Box>
    </SectionCardWrapper>
  );
}

export default SectionCard;
