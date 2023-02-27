<!DOCTYPE html>
<html>
  <head>
    <title>Processing, Please wait...</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="referrer" content="origin" />
    <meta name="viewport" content="width=device-width" />
    <meta charset="utf-8" />
    @include('partials.loader')
    <style>
      /* NPCI Feedback Popup */
      .modal {
        position: fixed;
        text-transform: none;
        z-index: 1;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgb(39 39 39 / 86%);
        opacity: 0;
        visibility: hidden;
        font-family: "Lato", sans-serif;

        transform: scale(1.1);
        transition: visibility 0s linear 0.25s, opacity 0.25s 0s,
          transform 0.25s;
      }

      .modal-content {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background-color: #ffffff;
        border: 1px solid #f8f9fb;
        min-width: 33em;
        max-width: 42em;
        box-shadow: 0px 1px 2px rgba(21, 45, 75, 0.2),
          0px 0px 1px rgba(21, 45, 75, 0.2);
        border-radius: 4px;
      }
      .mandate-info {
        padding: 5% 18%;
      }

      .modal-header {
        display: flex;
        justify-content: space-between;
        margin: 1em 0em;
        border-bottom: 1px solid #e6e6e8;
        padding: 1em 2em 1.5em 2em;
      }
      .modal-footer {
        padding: 2em;
        justify-content: center;
        display: flex;
      }
      .powered-by {
        font-size: 12px;
        line-height: 30px;
        padding-right: 7px;
        font-weight: 700;
      }

      .msg {
        font-family: "Lato", sans-serif;
        font-style: normal;
        font-weight: 400;
        font-size: 18px;
        line-height: 28px;
        text-align: center;
        display: block;
        padding-top: 1em;
      }
      .success {
        color: #008659;
      }
      .error {
        color: #d13821;
      }

      .npci-message-container {
        padding: 10px;
      }
      .npci-message {
        font-style: normal;
        font-weight: 700;
        font-size: 18px;
        line-height: 28px;
        color: #435775;
        text-align: center;
        display: block;
        padding: 1em 0em;
      }

      .mandate-summery-message {
        display: block;
        justify-content: center;
        padding: 34px 24px 24px 24px;
        color: #435775;
        font-size: 16px;
        font-weight: normal;
        text-align: center;
      }

      .show-modal {
        opacity: 1;
        visibility: visible;
        transform: scale(1);
        transition: visibility 0s linear 0s, opacity 0.25s 0s, transform 0.25s;
      }
      .success-tick {
        display: flex;
        justify-content: center;
      }
      .success-mandate {
        padding: 1em 4em;
      }
      .action {
        display: block;
        text-align: center;
      }
      #tick-mark {
        position: relative;
        display: inline-block;
        width: 30px;
        height: 30px;
      }

      #tick-mark::before {
        position: absolute;
        left: 0;
        top: 50%;
        height: 50%;
        width: 3px;
        background-color: #01b358;
        content: "";
        transform: translateX(10px) rotate(-45deg);
        transform-origin: left bottom;
      }

      #tick-mark::after {
        position: absolute;
        left: 0;
        bottom: 0;
        height: 3px;
        width: 100%;
        background-color: #01b358;
        content: "";
        transform: translateX(10px) rotate(-45deg);
        transform-origin: left bottom;
      }
      .btn-large {
        width: 284px;
        padding: 0px;
        margin: 0px;
      }
      .arrow {
        font-size: 22px;
        padding-left: 10px;
      }
      .failed {
        display: flex;
        justify-content: center;
      }
      .close {
        box-sizing: border-box;
        position: relative;
        display: block;
        transform: scale(var(--ggs, 1));
        width: 22px;
        height: 22px;
        border: 2px solid transparent;
        border-radius: 40px;
      }
      .close::after,
      .close::before {
        content: "";
        display: block;
        box-sizing: border-box;
        position: absolute;
        width: 32px;
        height: 3px;
        background: #d13821;
        transform: rotate(45deg);
        border-radius: 5px;
        top: 8px;
        left: 1px;
      }
      .close::after {
        transform: rotate(-45deg);
      }
      button {
        background: #528ff0;
        height: 46px;
        border-radius: 2px;
        margin-left: 20px;
        padding: 0 22px;
        border: 0;
        color: #fff;
        font-size: 16px;
        cursor: pointer;
      }
      button:disabled {
        opacity: 0.5;
      }
      /* EOF NPCI Feedback  */
    </style>
  </head>
  <body>
    <!-- NPCI FEEDBACK Modal -->
    <section>
      <div class="modal" id="npci-feedback-link-modal">
        <div class="modal-content">
          <div class="modal-header">
            <img height="32" src="https://cdn.razorpay.com/brand/npci.png" />

            <img height="24" src="https://cdn.razorpay.com/brand/nach.png" />
          </div>
          <div class="mandate-info">
            <div class="success-mandate">
              <div class="failed">
                <i class="close"></i>
              </div>
              <div class="msg error">
                Oops! Your e-mandate registration has failed.
              </div>
            </div>
            <div class="npci-message-container">
              <div class="npci-message">
                Please rate your experience to continue. It’ll only take a few
                seconds.
              </div>
              <div class="action">
                <button onclick="handleFeedbackLinkClick()" class="btn-large">
                  Proceed <span class="arrow">&#8594;</span>
                </button>
              </div>
            </div>
            <div class="mandate-summery-message">
              You may retry the e-mandate registration after sharing your
              feedback.
            </div>
          </div>
          <div class="modal-footer">
            <span class="powered-by">Powered by</span>
            <img height="26" src="https://cdn.razorpay.com/logo.svg" />
          </div>
        </div>
      </div>
    </section>
    <!-- END OF NPCI FEEDBACK Modal -->

    <form action="{{ $data['request']['url'] }}" method="post">
      @foreach ($data['request']['content'] as $key => $value)
      <input type="hidden" name="{{ $key }}" value="{{ $value }}" />
      @endforeach
      <!-- <input type="submit" /> -->
    </form>

    <form id="form2" name="form2">
      <input type="hidden" name="type" value="return" />
    </form>
  </body>
  <script>
    var iosBridge = window.webkit && webkit.messageHandlers && webkit.messageHandlers.CheckoutBridge;

    // ================= NPCI Feedback =========
    window.addEventListener("load", onLoad);
    document.addEventListener("readystatechange", onLoad);
    const NPCI_FEEDBACK_URL = "https://qdeg.in/5K2qabC";
    const feedbackPopup = document.getElementById("npci-feedback-link-modal");

    function onLoad() {
      if (!iosBridge && !window.CheckoutBridge) {
        openNPCIFeedbackModal();
      } else {
        closeFeedbackPopup();
      }
    }

    function openNPCIFeedbackModal() {
      feedbackPopup.classList.add("show-modal");
    }

    function closeFeedbackPopup() {
      feedbackPopup.classList.remove("show-modal");
      document.forms[0].submit();
    }

    function calculatePopupPosition() {
      const width = screen.width - Math.round((screen.width / 10) * 2);
      const height = screen.height - Math.round((screen.height / 10) * 2);
      const left = (screen.width - width) / 4;
      const top = (screen.height - height) / 3;
      return `resizable=yes, width=${width}, height=${height}, top=${top}, left=${left}`;
    }

    function handleFeedbackLinkClick() {
      try {
        const feedbackWindow = window.open(
          NPCI_FEEDBACK_URL,
          "_blank",
          calculatePopupPosition()
        );

        if (
          !feedbackWindow ||
          feedbackWindow.closed ||
          typeof feedbackWindow.closed == "undefined"
        ) {
          closeFeedbackPopup();
        } else {
          closeFeedbackPopup();
        }
      } catch {
        closeFeedbackPopup();
      }
    }
    // ================ END OF NPCIFeedback ===========
  </script>
</html>
