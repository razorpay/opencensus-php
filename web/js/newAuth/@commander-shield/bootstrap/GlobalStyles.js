import { createGlobalStyle } from 'styled-components';
import { SCREEN_TRANSITION_TIME_IN_MS } from '../screens/screenHelpers';

const GlobalStyles = createGlobalStyle`
  .slide-forward-enter,
  .slide-forward-exit, 
  .slide-backward-enter,
  .slide-backward-exit {
    transition: transform ${SCREEN_TRANSITION_TIME_IN_MS}ms ease-out;
  }

  .no-animation-enter,
  .no-animation-exit {
    transition: transform 0ms ease-out;
  }

  .slide-forward-enter,
  .no-animation-enter {
    transform: translateX(100%);
  }

  .slide-forward-enter.slide-forward-enter-active,
  .no-animation-enter.no-animation-enter-active {
    transform: translateX(0%);
  }

  .slide-forward-exit,
  .no-animation-exit,
  .slide-backward-exit {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    transform: translateX(0%);
  }

  .slide-forward-exit-active,
  .no-animation-exit-active {
    transform: translateX(-100%);
  }

  .slide-backward-enter {
    transform: translateX(-100%);
  }

  .slide-backward-enter.slide-backward-enter-active {
    transform: translateX(0%);
  }

  .slide-backward-exit-active {
    transform: translateX(100%);
  }
  
  .grecaptcha-badge {
    visibility: hidden;
  }
`;

export default GlobalStyles;
