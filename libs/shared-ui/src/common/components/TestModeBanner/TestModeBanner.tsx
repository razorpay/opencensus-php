import React, { useMemo } from "react";
import {
  Box,
  Text,
  Link,
  InfoIcon,
  Tooltip,
  ArrowRightIcon,
  TooltipInteractiveWrapper,
} from "@razorpay/blade/components";
import { isMobileDevice } from "@libs/shared-utils";

export interface TestModeBannerProps {
  title: string;
  tooltip?: {
    title: string;
    content: string;
  };
  linkLabel: string;
  onSwitchMode: () => void;
  marginTop?: any;
  marginLeft?: any;
  marginRight?: any;
  positionTop?: any;
}

export const TestModeBanner: React.FC<TestModeBannerProps> = ({
  title,
  tooltip = {
    title: "",
    content: "",
  },
  linkLabel,
  onSwitchMode,
  marginTop = 'spacing.0',
  marginLeft = 'spacing.0',
  marginRight = 'spacing.0',
  positionTop = 'spacing.0',
}) => {
  const isMobile = isMobileDevice();
  const shouldShowTooltip = (tooltip?.title === "" && tooltip?.content === "") ? false : true;
  return (
    <Box
      position='sticky'
      top={positionTop}
      left='0px'
      right='0px'
      zIndex='5'
      marginTop={marginTop}
      marginLeft={marginLeft}
      marginRight={marginRight}
    >
      <Box
        display="flex"
        justifyContent="space-between"
        alignItems="center"
        backgroundColor="feedback.background.notice.intense"
        padding="spacing.3"
        width="100%"
      >
        <Box display="flex" alignItems="center" marginLeft={isMobile ? "spacing.2" : "spacing.3"}>
          <Text size="small" weight="medium" color="surface.text.staticWhite.normal">
            {title}
          </Text>
          {!isMobile && shouldShowTooltip && (
            <Tooltip title={tooltip.title} content={tooltip.content} aria-label={`${tooltip.title}: ${tooltip.content}`}>
              <TooltipInteractiveWrapper>
                <InfoIcon color="surface.icon.staticWhite.normal" size="small" marginLeft="spacing.2" />
              </TooltipInteractiveWrapper>
            </Tooltip>
          )}
        </Box>

        <Link
          size="small"
          color="white"
          icon={ArrowRightIcon}
          iconPosition="right"
          marginRight={isMobile ? "spacing.2" : "spacing.3"}
          onClick={onSwitchMode}
        >
          {linkLabel}
        </Link>
      </Box>

    </Box>
  );
};  