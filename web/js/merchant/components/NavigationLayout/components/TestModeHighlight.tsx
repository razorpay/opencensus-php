import React, { useMemo } from 'react';
import { Box, Text, Theme } from '@razorpay/blade/components';
import { useStore } from '@federated/apps/shell/commonStore';
import styled from 'styled-components';

const TextWrapper = styled.div(
  ({ theme }: { theme: Theme }) => `
  width: fit-content;
  background-color: ${theme.colors.feedback.background.notice.intense};
  border-bottom-left-radius: ${theme.border.radius['2xlarge']}px;
  border-bottom-right-radius: ${theme.border.radius['2xlarge']}px;
  padding: ${theme.spacing[1]}px ${theme.spacing[6]}px;
`,
);

const Stroke = styled.div(
  ({ position }: { position: 'right' | 'left' }) => `
  width: 60px;
  max-width: 100%;
  height: 1px;
  background: ${
    position === 'left'
      ? `linear-gradient(90deg, rgba(233, 105, 12, 0) 0%, #E9690C 100%)`
      : `linear-gradient(90deg, #E9690C 0%, rgba(233, 105, 12, 0) 100%);
`
  };
`,
);

function TestModeHighlight({ product }): JSX.Element | null {
  let { mode, partnerMode } = useStore((state) => state.session);

  const { type, alias } = product;

  const currentMode = useMemo(() => {
    if (type === 'growth_page' || type === 'access_denied_page' || type === 'error_page') {
      return null;
    }

    if (alias === 'payments_top_navigation_item') {
      return mode;
    }

    if (alias === 'partnership_top_navigation_item') {
      return partnerMode;
    }

    return null;
  }, [type, alias, mode, partnerMode]);

  if (currentMode === null || currentMode === 'live') {
    return null;
  }

  return (
    <Box
      position="sticky"
      top="0px"
      zIndex="1"
      left="50%"
      width="fit-content"
      transform="translate(-50%, -16px)"
      display="flex"
    >
      <Stroke position="left" />
      <TextWrapper>
        <Text
          variant="body"
          size="small"
          weight="regular"
          color="surface.text.staticWhite.normal"
          as="span"
        >
          You are in{' '}
          <Text
            variant="body"
            size="small"
            weight="semibold"
            color="surface.text.staticWhite.normal"
            as="span"
          >
            Test Mode
          </Text>
        </Text>
      </TextWrapper>
      <Stroke position="right" />
    </Box>
  );
}

export default TestModeHighlight;
