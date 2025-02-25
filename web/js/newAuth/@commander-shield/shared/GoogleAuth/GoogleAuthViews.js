import styled from 'styled-components';
import View from '@razorpay/blade-old/src/atoms/View';

export const ButtonView = styled(View)`
  width: 100%;
  position: relative;
`;

export const Separator = styled(View)`
  background: ${(props) => props.theme.colors.shade[930]};
  border-radius: 2px;
  height: 1px;
`;

export const CenteredText = styled(View)`
  position: absolute;
  top: -13px;
  left: calc(50% - 20px);
  z-index: 1;
  background: ${(props) => props.theme.colors.background[100]};
`;

export const OneTapView = styled(View)`
  transform: scale(0.8);
  transform-origin: top left;
  margin: -6px 0px 0px -6px;
  iframe {
    max-width: 333px;
  }
  @media (max-width: 768px) {
    iframe {
      max-width: initial;
    }
  }
  @media (max-width: 365px) {
    iframe {
      max-width: 333px;
    }
  }
`;

export const SeparatorBlock = styled(View)`
  background: ${(props) => props.theme.colors.shade[930]};
  border-radius: 2px;
  height: 1px;
`;

export const OneTapWrapper = styled(View)`
  overflow: hidden;
  flex-shrink: 0;
`;

export const SeparatorView = styled(View)`
  position: relative;
`;
