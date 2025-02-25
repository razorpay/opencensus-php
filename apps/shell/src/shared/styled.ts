import { createGlobalStyle } from 'styled-components';

export const GlobalStyle = createGlobalStyle`
  body {
    margin: 0;
    padding: 0;
  };

  ::-webkit-scrollbar {
    width: 6px;
  }

  ::-webkit-scrollbar-track {
    background: linear-gradient(180deg, #cbd5e2 44%, #f1f5fa 72%, #fff 100%);
  }

  ::-webkit-scrollbar-thumb {
    background: gray;
    border-radius: 4px;
  }
  

  // To be removed after resolving webpack error overlay issue
  iframe {
    display: none;
  }

  #splash {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    border: 3px solid transparent;
    border-top-color: #528ff0;
    border-left-color: #528ff0;
    animation: spin 0.6s linear infinite;
    position: fixed;
    top: 50%;
    left: 50%;
  }

  @keyframes spin {
    0% {
      transform: rotate(0);
    }
    100% {
      transform: rotate(360deg);
    }
  }

  .Notifications {
    position: fixed;
    top: 60px;
    transform: translate(-50%,0);
    z-index: 10000000; // Above all
    left: 50%;
    width: 35%;
    min-width: 300px;
    max-width: 450px;
  }
  
  .Notification {
    padding: 10px 25px 10px 20px;
    opacity: 0;
    margin-top: -10px;
    margin-bottom: 10px;
    transition: all 0.25s ease-in-out;
    position: relative;
    border-radius: 4px;
  
    .i {
      position: absolute;
      top: 12px;
      right: 12px;
      cursor: pointer;
    }
  
    .list-unstyled {
      display: inline-block;
      margin-bottom: 0;
    }
  
    a {
      color: inherit;
      text-decoration: underline;
    }
  }
  
  .Notification__show {
    opacity: 1;
    margin-top: 0;
  }
  
  .Notification--success {
    background-color: #00BB55;
    color: #fff;
  }
  .Notification--neutral {
    background-color: #4c5a8e;
    color: #fff;
  }
  
  .Notification--error {
    background-color: #F56A6A;
    color: #fff;
  }
  
  .Notification--info {
    background-color: #2f96b4;
    color: #fff;
  }
  

  .ReactModal__Overlay {
    z-index: 5 !important;
  }
`;
