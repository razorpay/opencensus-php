import React from 'react';
import { Box, ModalFooter as BladeModalFooter } from '@razorpay/blade/components';

type ModalFooterProps = { children: React.ReactNode };
// Note: Blade's ModalFooter couldn't be used as an indirect child which is needed for multi-step form inside modal body.
const ModalFooter = ({ children }: ModalFooterProps): JSX.Element => (
  // This represents a ModalFooter container with absolute position
  <Box position="absolute" height="76px" width="100%" left="0px" bottom="0px">
    <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%" padding="spacing.5">
      {children}
    </Box>
  </Box>
);

// This component is needed to bypass Blade Modal's children validation check:
// https://github.com/razorpay/blade/blob/a1e7504/packages/blade/src/components/Modal/Modal.web.tsx#L129
// it verifies using the props mdxType and originalType.componentId
type ConditionalModalFooterType = {
  shouldShowFooter: boolean;
  // eslint-disable-next-line react/no-unused-prop-types
  mdxType: string;
  // eslint-disable-next-line react/no-unused-prop-types
  originalType: { componentId: string };
};

export const ConditionalModalFooter = ({
  shouldShowFooter,
}: ConditionalModalFooterType): JSX.Element | null => {
  if (shouldShowFooter)
    return (
      <BladeModalFooter>
        {/* // Note: below is a placeholder to correct scrolling height in the DOM for ModalBody
            // because in our use-case, the ModalFooter is inside ModalBody.
            // The placeholder represents Buttons with height 36px; */}
        <Box backgroundColor="surface.background.gray.intense" height="36px" width="100%" />
      </BladeModalFooter>
    );
  return null;
};

export default ModalFooter;
