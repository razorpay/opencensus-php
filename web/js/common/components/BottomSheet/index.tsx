import React, { useState, ReactNode } from 'react';
import { BottomSheet } from 'react-spring-bottom-sheet';
import styled from 'styled-components';

// adding the css file to the dom
const cssLinkElement = document.createElement('link');
cssLinkElement.rel = 'stylesheet';
cssLinkElement.href = 'https://unpkg.com/react-spring-bottom-sheet/dist/style.css';
cssLinkElement.crossOrigin = 'anonymous';
document.body.appendChild(cssLinkElement);

/* 
overrding react-spring-bottom-sheet zIndex as our help and support icons comes on top of it
hence making our bottom sheet unusable. Hence increasing zIndex of bottom sheet to come
at the top level.
*/
const StyledBottomSheet = styled(BottomSheet)`
  [data-rsbs-overlay],
  [data-rsbs-backdrop],
  [data-rsbs-root]:after {
    z-index: 10000;
  }
`;

/*
  isOpen and isControlled to be used if we want to use bottom sheet as a controlled component
  otherwise, the state is handled internally, for example in cases where we areonly showing some
  static data
*/
interface ButtomSheetPropsT {
  isOpen?: boolean;
  isControlled?: boolean;
  trigger?: ReactNode;
  onTriggerClick?: () => void;
  onDismiss: () => void;
  isBlocking?: boolean;
  snapPoints?: () => number | number[];
  className?: string;
}
const _BottomSheet: React.FC<ButtomSheetPropsT> = (props) => {
  const [isOpen, setOpen] = useState(false);

  const {
    trigger,
    onDismiss,
    onTriggerClick,
    isControlled = false,
    isBlocking = true,
    snapPoints,
    className,
  } = props;

  const handleDismiss = () => {
    if (isControlled) {
      onDismiss?.();

      return;
    }

    setOpen(false);
  };

  const handleTriggerClick = (e) => {
    e.preventDefault();

    if (isControlled) {
      onTriggerClick?.();

      return;
    }

    setOpen(true);
  };

  return (
    <>
      {/* This will be the element on the click of which the bottom sheet will open */}
      {trigger && <div onClick={handleTriggerClick}>{trigger}</div>}

      <StyledBottomSheet
        open={isControlled ? props.isOpen! : isOpen!}
        onDismiss={handleDismiss}
        blocking={isBlocking}
        snapPoints={snapPoints}
        className={className}
      >
        {props.children}
      </StyledBottomSheet>
    </>
  );
};

export default _BottomSheet;
