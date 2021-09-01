import React, { useState, useEffect, useRef, ReactNode } from 'react';
import { TooltipContainer, InputInfo } from './Styled';

interface OverlayObj {
  [name: string]: string;
}

export interface TooltipPropsT {
  overlay: string | OverlayObj;
  children: ReactNode;
}

export interface InfoPropsT {
  overlayText: string | OverlayObj;
}

const Info: React.FC<InfoPropsT> = ({ overlayText }) => {
  if (overlayText) {
    return (
      <InputInfo data-testid="infoTooltip">
        {typeof overlayText === 'object' ? (
          <ul data-testid="infoList">
            {Object.keys(overlayText).map((key) => (
              <li key={key}>
                <b>{key}:</b> {overlayText[key]}
              </li>
            ))}
          </ul>
        ) : (
          overlayText
        )}
      </InputInfo>
    );
  }
  return null;
};

const InfoTooltip: React.FC<TooltipPropsT> = ({ overlay, children }) => {
  const [isVisible, setIsVisible] = useState<boolean>(false);
  const textInputContainer = useRef<null | HTMLScriptElement>(null);
  useEffect(() => {
    const inputEl =
      textInputContainer?.current?.getElementsByTagName('input') &&
      textInputContainer?.current?.getElementsByTagName('input')[0];
    if (inputEl) {
      inputEl.addEventListener('focusin', () => {
        setIsVisible(true);
      });
      inputEl.addEventListener('focusout', () => {
        setIsVisible(false);
      });
    }
    return () => {
      if (inputEl) {
        inputEl.removeEventListener('focusin', () => {});
        inputEl.removeEventListener('focusout', () => {});
      }
    };
  }, []);

  return (
    <TooltipContainer ref={textInputContainer}>
      {children}
      {isVisible && <Info overlayText={overlay} />}
    </TooltipContainer>
  );
};

export default InfoTooltip;
