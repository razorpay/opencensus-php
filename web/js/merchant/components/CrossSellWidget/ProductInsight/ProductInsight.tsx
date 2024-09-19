import React from 'react';
import {
  Box,
  Text,
  Heading,
  Badge,
  Display,
  Button,
  Divider,
  Avatar,
  CheckIcon,
} from '@razorpay/blade/components';
import sanitizer from 'common/utils/xss-sanitizer';

type DisplayColors =
  | 'feedback.text.information.intense'
  | 'feedback.text.negative.intense'
  | 'feedback.text.positive.intense';

interface ProductInsightProps {
  badges?: string[];
  logoImages?: string[];
  displayText: string;
  aboveDisplayHeading?: string;
  belowDisplayHeading?: string;
  captionList?: string[];
  actionCta?: string;
  bodyText?: string;
  postPitchCaption?: string;
  displayColor?: string;
  actionClickHandler?: () => void;
}

function ProductInsight({
  badges,
  logoImages,
  displayText,
  aboveDisplayHeading,
  belowDisplayHeading,
  captionList,
  actionCta,
  bodyText,
  actionClickHandler,
  postPitchCaption,
  displayColor,
}: ProductInsightProps): JSX.Element {
  const shouldShowBadges = badges && badges.length > 0;
  const shouldShowLogoImages = logoImages && logoImages.length > 0;
  const showCtaSection = shouldShowLogoImages || bodyText || actionCta;
  const shouldShowCaptionList = captionList && captionList.length > 0;

  const handleActionClick = () => {
    if (actionClickHandler) {
      actionClickHandler();
    }
  };

  return (
    <>
      {shouldShowBadges && (
        <Box marginBottom="spacing.4">
          {badges.map((badge) => (
            <Badge key={badge} color="neutral">
              {badge}
            </Badge>
          ))}
        </Box>
      )}

      {aboveDisplayHeading && (
        <Heading size="large" weight="semibold" color="feedback.text.neutral.intense">
          <p dangerouslySetInnerHTML={{ __html: sanitizer(aboveDisplayHeading) }} />
        </Heading>
      )}
      {displayText && (
        <Display
          size="small"
          weight="semibold"
          color={(displayColor as DisplayColors) || 'feedback.text.information.intense'}
        >
          {displayText}
        </Display>
      )}
      {belowDisplayHeading && (
        <Heading size="large" weight="semibold" color="feedback.text.neutral.intense">
          {belowDisplayHeading}
        </Heading>
      )}

      {postPitchCaption && (
        <Text
          variant="caption"
          color="surface.text.gray.muted"
          size="medium"
          weight="regular"
          marginTop="spacing.3"
        >
          {postPitchCaption}
        </Text>
      )}

      {shouldShowCaptionList && (
        <Box marginTop="spacing.3">
          {captionList.map((caption) => (
            <Box display="flex" alignItems="center" key={caption}>
              <Box
                width="spacing.5"
                height="spacing.5"
                display="flex"
                justifyContent="center"
                alignItems="center"
                borderRadius="round"
                backgroundColor="surface.background.gray.subtle"
                marginRight="spacing.2"
              >
                <CheckIcon size="small" />
              </Box>
              <Text variant="body" size="medium" weight="regular">
                {caption}
              </Text>
            </Box>
          ))}
        </Box>
      )}

      {showCtaSection && (
        <Box marginTop="100px" marginRight="25%">
          {shouldShowLogoImages && (
            <>
              <Divider width="36px" marginBottom="spacing.4" />
              <Box marginBottom="spacing.4" display="flex" gap="spacing.1">
                {logoImages.map((logo) => (
                  <Avatar key={logo} src={logo} name="Brand logo" size="small" />
                ))}
              </Box>
            </>
          )}

          {bodyText && (
            <Text variant="body" color="surface.text.gray.muted" size="medium" weight="medium">
              {bodyText}
            </Text>
          )}
          {actionCta && (
            <Button marginTop="spacing.5" onClick={handleActionClick}>
              {actionCta}
            </Button>
          )}
        </Box>
      )}
    </>
  );
}

export default ProductInsight;
