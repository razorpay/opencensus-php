import { trackLinkClick } from './ga';

export default () => {
  return (
    <footer class="pagefooter">
      © 2017 Copyright Razorpay ·{' '}
      <u>
        <a
          href="https://razorpay.com/agreement/"
          target="_blank"
          onClick={trackLinkClick}
        >
          Merchant Agreement
        </a>
      </u>{' '}
      ·{' '}
      <u>
        <a
          href="https://razorpay.com/terms/"
          target="_blank"
          onClick={trackLinkClick}
        >
          Terms of Use
        </a>
      </u>{' '}
      ·{' '}
      <u>
        <a
          href="https://razorpay.com/privacy/"
          target="_blank"
          onClick={trackLinkClick}
        >
          Privacy Policy
        </a>
      </u>{' '}
      ·{' '}
      <u>
        <a href="mailto:contact@razorpay.com" onClick={trackLinkClick}>
          Contact Us
        </a>
      </u>
    </footer>
  );
};
