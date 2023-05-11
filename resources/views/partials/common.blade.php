</head>
<body>
  <div id="react-root" class="react-root"></div>

  <style>
    html {
      background-color: #f0f3f4;
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
  </style>

  <div id="splash"></div>

  <div class="app" id="app"></div>

