@extends('layouts.nach')
@section('content')
  <style>
    button[type=submit] {
      display: block;
      margin: 20px auto 0;
    }
    .result {
      padding: 20px;
      margin: 40px -30px 0;
      background: #F5FFFC;
      border-top: 1px solid #67D2AC;
      border-bottom: 1px solid #67D2AC;
      text-transform: none;
    }
  </style>
  <?php
    if (isset($data['request'])) {
      $error = $data['request']['content']['error[description]'] ?? '';
      $razorpay_payment_id = $data['request']['content']['razorpay_payment_id'] ?? '';
    } else {
      $error = $data['error']['description'] ?? '';
      $razorpay_payment_id = $data['razorpay_payment_id'] ?? '';
    }
    if ($razorpay_payment_id) {
      echo '<div class="result">Your E-Mandate registration is successfully completed. Your reference ID for E- Mandate registration is <strong>' . $data['emandate_details']['reference_number'] . '</strong></div>';
    } else {
      echo '<div class="result" style="background: #FFD5D5; border-color: #FF8080">' . $error . '</div>';
    }
  ?>
  @if (isset($data['request']))
    <form action="{{$data['request']['url']}}" method="post">
      @foreach ($data['request']['content'] as $key => $value)
        <input type="hidden" name="{{$key}}" value="{{$value}}">
      @endforeach
      <button type="submit">Proceed</button>
    </form>
  @else
    <button type="submit" onclick="paymentCallback(this)">Proceed</button>
    <script>
        // Callback data //
      var data = {!!utf8_json_encode($data)!!}; // Callback data //

      // ================= NPCI Feedback =========
      window.addEventListener("load", onLoad);
      document.addEventListener("readystatechange", onLoad);

      var NPCI_FEEDBACK_URL = "https://qdeg.in/5K2qabC";
      var feedbackPopup = document.getElementById("npci-feedback-link-modal");
      var isPostMandateRegistration = !!data['request'];
      var isFeedbackAllowed = !!data['allow_feedback']
      function onLoad() {
        openNPCIFeedbackModal();
      }
      
      function openNPCIFeedbackModal() {
        // If data[request] is not set, it indicates post mandate registration summery page
        // Show only when allow_feedback param is present
        if (isPostMandateRegistration && isFeedbackAllowed) {
          feedbackPopup.classList.add("show-modal");
        } else {
          closeFeedbackPopup();
        }
      }
      
      function closeFeedbackPopup() {
        feedbackPopup.classList.remove("show-modal");
      }
      
      function handleFeedbackLinkClick() {
        try {
          const feedbackWindow = window.open(NPCI_FEEDBACK_URL, "_blank");
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

      var iosBridge = window.webkit && webkit.messageHandlers && webkit.messageHandlers.CheckoutBridge;

      function paymentCallback(btnElm) {
        btnElm.disabled=true;
        if (window.CheckoutBridge) {
          CheckoutBridge.oncomplete(JSON.stringify(data));
        } else if (iosBridge) {
          iosBridge.postMessage({
            action: 'success',
            body: data
          });
        } else {
          try { window.opener.onComplete(data) } catch(e){}
          try { (window.opener || window.parent).postMessage(data, '*') } catch(e){}
          setTimeout(close, 999);
        }
      }
    </script>
  @endif
@endsection
