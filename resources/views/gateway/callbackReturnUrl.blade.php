<!doctype html>
<html>
  <head>
    <title>Processing, Please wait...</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="referrer" content="origin">
    <meta name="viewport" content="width=device-width">
    <meta charset="utf-8">

    @include('partials.loader')
    @if (isset($data['payment_details']) === true &&
    isset($data['payment_details']['is_email_less_payment']) === true &&
    isset($data['payment_details']['payment_id']) === true &&
    isset($data['payment_details']['library']) === true &&
    $data['payment_details']['is_email_less_payment'] === true &&
    ($data['payment_details']['library'] === "checkoutjs" || $data['payment_details']['library'] === "hosted"))
      <style>
        html {
          font-size: 16px;
          font-family: sans-serif;
        }
        * {
          box-sizing: border-box;
          border-width: 0;
          border-style: solid;
        }
        body {
          margin: 0;
        }
        #checkoutTransitionScreen .post-payment-container {
          min-height: 100vh;
          display: flex;
          align-items: center;
          justify-content: center;
        }
        #checkoutTransitionScreen .checkout {
          width: 375px;
          height: 640px;
          display: flex;
          overflow: hidden;
          position: relative;
          border-radius: 8px;
          align-items: flex-start;
          flex-shrink: 0;
          border-color: transparent;
          background-color: rgba(255, 255, 255, 1);
        }

        #checkoutTransitionScreen .post-payment-body {
          bottom: 0;
          left: 0;
          width: 100%;
          height: 203px;
          display: flex;
          position: absolute;
          flex-direction: column;
          padding: 20px 20px 40px;
          justify-content: space-between;
        }
        #checkoutTransitionScreen .post-payment-timestamp {
          color: #162f568a;
          font-size: 12px;
        }
        #checkoutTransitionScreen .post-payment-title {
          width: 100%;
          height: 24px;
          padding-bottom: 8px;
          box-sizing: content-box;
          display: flex;
          align-items: center;
        }
        #checkoutTransitionScreen .post-payment-text56 {
          color: #162f56de;
          font-size: 20px;
          font-weight: 600;
          margin-right: 4px;
          margin-bottom: 0;
          max-width: 100%;
          text-overflow: ellipsis;
          overflow: hidden;
          white-space: nowrap;
        }
        #checkoutTransitionScreen .post-payment-r-t-b3 {
          width: 22px;
          height: 22px;
          position: relative;
          border-color: transparent;
        }
        #checkoutTransitionScreen .post-payment-method {
          display: flex;
          align-items: flex-start;
          border-color: transparent;
          margin-bottom: 8px;
        }
        #checkoutTransitionScreen .pp-method-id {
          color: rgba(22, 47, 86, 0#checkoutTransitionScreen .5400000214576721);
          font-size: 16px;
          margin-right: 8px;
        }
        #checkoutTransitionScreen .pp-method {
          color: #162f568a;
          font-weight: 400;
          text-transform: capitalize;
        }
        #checkoutTransitionScreen .post-payment-copyicon {
          cursor: pointer;
          width: 18px;
          height: 18px;
          display: flex;
          position: relative;
          box-sizing: border-box;
          align-items: flex-start;
          background-color: rgba(217, 217, 217, 0);
        }
        #checkoutTransitionScreen .post-payment-support-container {
          width: 100%;
          height: 13px;
          display: flex;
          padding: 0;
          position: relative;
          align-self: auto;
          box-sizing: border-box;
          align-items: flex-start;
          flex-shrink: 1;
          flex-direction: row;
          justify-content: flex-start;
          background-color: transparent;
        }
        #checkoutTransitionScreen .post-payment-support {
          left: 12px;
          position: relative;
          font-size: 11px;
          color: #162f568a;
          font-weight: 400;
        }
        #checkoutTransitionScreen .post-payment-support-link {
          color: #162f56de;
          font-weight: 500;
        }
        #checkoutTransitionScreen .post-payment-infoicon {
          top: -5px;
          left: 0;
          width: 8px;
          height: 8px;
          position: absolute;
        }
        #checkoutTransitionScreen .post-payment-footer {
          width: 100%;
          bottom: 0;
          left: 0;
          height: 40px;
          display: flex;
          align-items: center;
          padding: 10px 20px;
          color: rgba(63, 113, 215, 1);
          background-color: #f3f9fd;
          position: absolute;
          font-size: 11px;
          line-height: 20px;
          text-decoration: underline;
        }
        /* status part */
        #checkoutTransitionScreen .post-payment-status-screen {
          width: 100%;
          height: calc(100% - 203px);
          display: flex;
          align-items: center;
          flex-shrink: 0;
          flex-direction: column;
          justify-content: center;
          background: linear-gradient(97.21deg, rgba(255, 255, 255, 0.2) 0%, rgba(0, 0, 0, 0.2) 100%);
          background-color: rgba(31, 137, 14, 1);
        }

        #checkoutTransitionScreen .post-payment-status-screen.failure {
            background-color: #ba3737;
        }

        #checkoutTransitionScreen .post-payment-status-icon {
          width: 85px;
          height: 85px;
          border: 8px solid rgba(255, 255, 255, 0.2);
          border-radius: 50%;
          display: flex;
          align-items: center;
          margin-bottom: 20px;
          justify-content: center;
        }
        #checkoutTransitionScreen .post-payment-success-icon {
          width: 72px;
          height: 72px;
        }
        #checkoutTransitionScreen .post-payment-message {
          color: #fff;
          font-size: 16px;
          font-weight: 600;
          margin-bottom: 20px;
        }
        #checkoutTransitionScreen .post-payment-amount {
          color: #ffffff;
          font-size: 24px;
          font-weight: 500;
        }

        @media (max-width: 450px) {
          #checkoutTransitionScreen .checkout {
            width: 100vw;
            height: 100vh;
          }
        }
        @media (max-width: 991px) {
          #checkoutTransitionScreen .checkout {
            width: 344px;
            height: 600px;
          }
        }
      </style>
      <script>
        window.onload = function() {
          var timer = 6;
          var interval = setInterval(function () {
            var timeout = document.getElementById('timeout');
            if(timer === 0) {
              document.forms[0].submit();
              clearInterval(interval);
              return;
            }
            if(timeout && timer > 0) {
              timeout.innerText = --timer;
            }
          }, 1000);
        }
        function copyToClipboard(selector, refData) {
          var selectedElement = document.querySelector(selector);
          var textArea = document.createElement('textarea');
          try {
            textArea.value = refData;
            selectedElement.appendChild(textArea);
            textArea.select();
            return document.execCommand('copy');
          } catch (err) {
            return false;
          } finally {
            selectedElement.removeChild(textArea);
          }
        };

      </script>
    @else
        <script>
          window.onload = function() {
            document.forms[0].submit();
          }
        </script>
    @endif
  </head>
  <body>
    @if (isset($data['payment_details']) === true &&
    isset($data['payment_details']['is_email_less_payment']) === true &&
    isset($data['payment_details']['payment_id']) === true &&
    isset($data['payment_details']['library']) === true &&
    $data['payment_details']['is_email_less_payment'] === true &&
    ($data['payment_details']['library'] === "checkoutjs" || $data['payment_details']['library'] === "hosted"))
      <div id="checkoutTransitionScreen">
        <div
          style="
            height: 100vh;
            position: fixed;
            width: 100vw;
            background: #666666;
          "
        ></div>
        <div
          class="post-payment-container"
          style="display: flex; align-items: center; justify-content: center"
        >
          <div class="checkout">
            @if ($data['payment_details']['success'])
              <div class="post-payment-status-screen">
            @else
              <div class="post-payment-status-screen failure">
            @endif
              <div class="post-payment-status-icon">
              @if ($data['payment_details']['success'])
                <svg width='73' height='73' viewBox='0 0 73 73' fill='none' xmlns='http://www.w3.org/2000/svg'>
                  <path d='M36.5 0.5C16.628 0.5 0.5 16.628 0.5 36.5C0.5 56.372 16.628 72.5 36.5 72.5C56.372 72.5 72.5 56.372 72.5 36.5C72.5 16.628 56.372 0.5 36.5 0.5ZM26.744 51.944L13.82 39.02C13.4867 38.6867 13.2223 38.291 13.0419 37.8556C12.8616 37.4201 12.7687 36.9533 12.7687 36.482C12.7687 36.0106 12.8616 35.5439 13.0419 35.1084C13.2223 34.673 13.4867 34.2773 13.82 33.944C14.1533 33.6107 14.549 33.3463 14.9844 33.1659C15.4199 32.9856 15.8867 32.8927 16.358 32.8927C16.8294 32.8927 17.2961 32.9856 17.7316 33.1659C18.167 33.3463 18.5627 33.6107 18.896 33.944L29.3 44.312L54.068 19.544C54.7411 18.8709 55.6541 18.4927 56.606 18.4927C57.5579 18.4927 58.4709 18.8709 59.144 19.544C59.8171 20.2171 60.1953 21.1301 60.1953 22.082C60.1953 23.0339 59.8171 23.9469 59.144 24.62L31.82 51.944C31.4869 52.2777 31.0913 52.5425 30.6558 52.7232C30.2203 52.9038 29.7535 52.9968 29.282 52.9968C28.8105 52.9968 28.3437 52.9038 27.9082 52.7232C27.4726 52.5425 27.077 52.2777 26.744 51.944Z' fill='white'/>
                </svg>
              @else
                <svg width="72" height="72" viewBox="0 0 72 72" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M36 0C16.092 0 0 16.092 0 36C0 55.908 16.092 72 36 72C55.908 72 72 55.908 72 36C72 16.092 55.908 0 36 0ZM51.462 46.386C52.8637 47.7877 52.8637 50.0603 51.462 51.462C50.0603 52.8637 47.7877 52.8637 46.386 51.462L36 41.076L25.614 51.462C24.2123 52.8637 21.9397 52.8637 20.538 51.462C19.1363 50.0603 19.1363 47.7877 20.538 46.386L30.924 36L20.538 25.614C19.1363 24.2123 19.1363 21.9397 20.538 20.538C21.9397 19.1363 24.2123 19.1363 25.614 20.538L36 30.924L46.386 20.538C47.7877 19.1363 50.0603 19.1363 51.462 20.538C52.8637 21.9397 52.8637 24.2123 51.462 25.614L41.076 36L51.462 46.386Z" fill="white"/>
                </svg>
              @endif

              </div>
              <span class="post-payment-message">
                <span>Payment
                  @if ($data['payment_details']['success'])
                    successful
                  @else
                    failed
                  @endif
                </span>
              </span>
              <span class="post-payment-amount">
                <span>
                {!! e(encode_currency($data['payment_details']['amount']), false) !!}</span>
              </span>
            </div>
            <div class="post-payment-body">
              <div class="merchant-data">
                <div class="post-payment-title">
                  <span class="post-payment-text56">
                    <span>{{ $data['payment_details']['merchant']['name'] }}</span>
                  </span>
                  @if ($data['payment_details']['merchant']['rtb'] === true)
                    <img
                      src="https://cdn.razorpay.com/static/assets/trustedbadge/rtb-live.svg"
                      alt="RTB"
                      class="post-payment-r-t-b3"
                    />
                  @endif
                </div>
                <span class="post-payment-timestamp">
                  <span>{{ $data['payment_details']['created_at'] }}</span>
                </span>
              </div>
              <div style="height: 70px">
                <div class="post-payment-method">
                  <span class="pp-method-id">
                    <span class="pp-method">{{ $data['payment_details']['method'] }} |</span>
                    <span id="paymentId">{{ $data['payment_details']['payment_id'] }}</span>
                  </span>
                  <div onclick="copyToClipboard('body', '{{ $data['payment_details']['payment_id'] }}')" class="post-payment-copyicon">
                  <svg width='15' height='18' viewBox='0 0 15 18' fill='none' xmlns='http://www.w3.org/2000/svg'>
                    <path d='M11.0001 0.749756H2.00006C1.17506 0.749756 0.500061 1.42476 0.500061 2.24976V12.7498H2.00006V2.24976H11.0001V0.749756ZM13.2501 3.74976H5.00006C4.17506 3.74976 3.50006 4.42476 3.50006 5.24976V15.7498C3.50006 16.5748 4.17506 17.2498 5.00006 17.2498H13.2501C14.0751 17.2498 14.7501 16.5748 14.7501 15.7498V5.24976C14.7501 4.42476 14.0751 3.74976 13.2501 3.74976ZM13.2501 15.7498H5.00006V5.24976H13.2501V15.7498Z' fill='#4B95ED'/>
                  </svg>
                  </div>
                </div>
                <div class="post-payment-support-container">
                  <span class="post-payment-support">
                    <span>Visit</span>
                    <a
                      href="https://razorpay.com/support"
                      target="_blank"
                      class="post-payment-support-link"
                    >
                      razorpay.com/support
                    </a>
                    <span>for queries</span>
                  </span>
                  <div class="post-payment-infoicon">
                    <svg width='8' height='8' viewBox='0 0 8 8' fill='none' xmlns='http://www.w3.org/2000/svg'>
                      <path d='M3.6 2H4.4V2.8H3.6V2ZM3.6 3.6H4.4V6H3.6V3.6ZM4 0C1.792 0 0 1.792 0 4C0 6.208 1.792 8 4 8C6.208 8 8 6.208 8 4C8 1.792 6.208 0 4 0ZM4 7.2C2.236 7.2 0.8 5.764 0.8 4C0.8 2.236 2.236 0.8 4 0.8C5.764 0.8 7.2 2.236 7.2 4C7.2 5.764 5.764 7.2 4 7.2Z' fill='#162F56' fill-opacity='0.54'/>
                    </svg>
                  </div>
                </div>
              </div>
            </div>

            <span class="post-payment-footer">
              <span>Redirecting in <span id="timeout">6</span> seconds...</span>
            </span>
          </div>
        </div>
      </div>
    @endif


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
</html>
