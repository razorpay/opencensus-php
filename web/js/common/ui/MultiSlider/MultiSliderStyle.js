import styled from 'styled-components';

export const MultiSlider__Component = styled.div.attrs((props) => ({
  className: props.classString,
}))`
  position: fixed;
  left: ${(props) => (props.position === 'left' ? '230px' : 'auto')};
  right: ${(props) => (props.position === 'left' ? 'auto' : '0')};
  top: 51px;
  height: 100%;
  z-index: ${(props) => (props.position === 'left' ? '1110' : '10000')};
  width: ${(props) => props.width}px;
  background: white;

  @media screen and (max-width: 768px) {
    top: 60px;
  }

  &.MultiSlider__Component--transition-enter {
    opacity: 0;
    transform: ${(props) =>
      props.position === 'left' ? `translateX(-${props.width}px)` : `translateX(${props.width}px)`};
  }

  &.MultiSlider__Component--transition-enter-active {
    opacity: 1;
    transform: translateX(0);
    transition: opacity ${(props) => props.transitionDuration}ms linear,
      transform ${(props) => props.transitionDuration}ms linear;
  }

  &.MultiSlider__Component--transition-exit-active {
    opacity: 0;
    transform: ${(props) =>
      props.position === 'left' ? `translateX(-${props.width}px)` : `translateX(${props.width}px)`};
    transition: opacity ${(props) => props.transitionDuration}ms linear,
      transform ${(props) => props.transitionDuration}ms linear;
  }

  button.close {
    padding: 10px;
    position: absolute;
    top: 16px;
    margin-left: -53px;
    left: 100%;
    z-index: 1110;
  }

  @media (max-width: 650px) {
    max-width: 92%;
  }
`;

export const MultiSliderOverlay = styled.div`
  position: fixed;
  top: 51px;
  width: 100%;
  height: 100%;
  z-index: 1109;
  background: rgba(0, 0, 0, 0.6);
  transition: all 0.3s linear;

  @media screen and (max-width: 768px) {
    top: 60px;
  }

  &.MultiSlider__Overlay--transition-enter {
    opacity: 0;
  }

  &.MultiSlider__Overlay--transition-enter-active {
    opacity: 1;
  }

  &.MultiSlider__Overlay--transition-exit {
    opacity: 1;
  }

  &.MultiSlider__Overlay--transition-exit-active {
    opacity: 0;
  }
`;

export const multiSliderAttributes = ({ size = 'medium', transitionSpeed = 'medium' }) => {
  const widthOptions = {
    small: 400,
    medium: 480,
    large: 640,
  };
  const transitionDurationOptions = {
    slow: 400,
    medium: 300,
    fast: 200,
  };

  const style = {
    width: widthOptions[size] || widthOptions.medium,
    transitionDuration:
      transitionDurationOptions[transitionSpeed] || transitionDurationOptions.medium,
  };

  return style;
};
