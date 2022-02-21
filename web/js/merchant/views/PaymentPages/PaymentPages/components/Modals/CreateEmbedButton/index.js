import React from 'react';
import { connect } from 'react-redux';
import ModalHeader from 'common/ui/ModalHeader';
import Button from 'common/new-ui/Button';
import Input from 'common/new-ui/Input';
import CustomClipboard from 'common/ui/Clipboard/Custom';
import { closeModal } from 'merchant_common/reducers/modals';
import PreviewEmbedButton from './PreviewEmbedButton';
import { trackCreateButtonSizeSelection, trackCreateButtonCancel } from '../../../ga';

const BTN_SIZES = ['Large', 'Medium', 'Small'];

@connect((state) => ({ config: state.config.config }), { closeModal })
export default class extends React.Component {
  state = { btnSize: '0', btnLabel: 'Pay Now' };

  updateButtonText = (e) => {
    this.setState({
      btnLabel: e.target.value,
    });
  };

  updateButtonSize = (e) => {
    this.setState({
      btnSize: e.target.value,
    });
  };

  get merchantThemeColor() {
    return this.props.config.brand_color;
  }

  render() {
    const { closeModal, id } = this.props;
    const { btnLabel, btnSize } = this.state;
    // const el = document.getElementById('embed-btn-preview');

    /* Embed Button */

    const scriptURL = 'https://cdn.razorpay.com/static/embed_btn/bundle.js';
    const buttonClass = 'razorpay-embed-btn';
    const scriptTagID = 'razorpay-embed-btn-js';
    const pageUrl = `https://pages.razorpay.com/${id}/view`;

    const embedBtnCode = `<div class="${buttonClass}" data-url="${pageUrl}" data-text="${
      this.state.btnLabel
    }" data-color="${this.merchantThemeColor}" data-size="${BTN_SIZES[btnSize].toLowerCase()}">
  <script>
    (function(){
      var d=document; var x=!d.getElementById('${scriptTagID}')
      if(x){ var s=d.createElement('script'); s.defer=!0;s.id='${scriptTagID}';
      s.src='${scriptURL}';d.body.appendChild(s);} else{var rzp=window['__rzp__'];
      rzp && rzp.init && rzp.init()}})();
  </script>
</div>
    `;

    return (
      <div>
        <ModalHeader
          title="Create Hyperlink Button"
          onCloseClick={() => {
            closeModal();
            trackCreateButtonCancel();
          }}
        />

        <div class="modal-body embed-button-form" style={{ paddingTop: 0 }}>
          <div class="ModalForm ModalForm--Share">
            <Input
              label="What will the button say?"
              class="Input--vTop"
              placeholder="Enter button text"
              onChange={this.updateButtonText}
              value={btnLabel}
              autoFocus
            />
            <Input.Radio
              label="Button size"
              options={BTN_SIZES}
              class="Input--vTop"
              value={btnSize}
              onChange={this.updateButtonSize}
            />
            <div class="Input Input--vTop Input--radio">
              <div class="Input-label">Preview</div>
              <PreviewEmbedButton url={pageUrl} btnSize={btnSize} btnLabel={btnLabel} />
            </div>
            <Input.Textarea
              id="code-copier"
              label={() => (
                <div>
                  HTML Code
                  <div class="description">
                    Copy & Paste this HTML in your code
                    <CustomClipboard value={embedBtnCode}>
                      <button
                        class="btn btn-link btn-xs"
                        onClick={() => trackCreateButtonSizeSelection(BTN_SIZES[btnSize])}
                      >
                        <i class="i i-copy" style={{ marginRight: 4 }} />
                        Copy
                      </button>
                    </CustomClipboard>
                  </div>
                </div>
              )}
              class="Input--vTop"
              value={embedBtnCode.trim()}
              readOnly
            />

            <br />
            <Button.Primary
              class="btn-block"
              onClick={() => {
                closeModal();
                trackCreateButtonCancel();
              }}
            >
              Done
            </Button.Primary>
          </div>
        </div>
      </div>
    );
  }
}

@connect(
  (state) => ({
    config: state.config.config,
  }),
  null,
)
// eslint-disable-next-line no-unused-vars
class PreviewPaymentPageButton extends React.Component {
  state = {
    textColor: '#fff',
  };

  componentDidMount() {
    const script = document.createElement('script');

    script.onload = () => {
      const textColor =
        !window.colorLib || window.colorLib.isDark(this.merchantThemeColor)
          ? '#fff'
          : 'rgba(0, 0, 0, 0.85)';

      this.setState({
        textColor,
      });
    };
    script.src = 'https://cdn.razorpay.com/static/assets/color.js';

    document.head.appendChild(script);
  }

  get merchantThemeColor() {
    return this.props.config.brand_color;
  }

  render() {
    const { url, btnSize = '0', btnLabel = 'Pay Now' } = this.props;

    let width;
    switch (btnSize) {
      case '1':
        width = 180;
        break;
      case '2':
        width = 120;
        break;
      default:
        width = 240;
    }

    const previewBtnCode = (
      <span>
        <a
          href={url}
          style={{
            position: 'relative',
            display: 'block',
            minHeight: 38,
            width,
            padding: 10,
            margin: '0 auto',
            lineHeight: '18px',
            fontWeight: 600,
            fontSize: 14,
            fontFamily: 'Lato, Muli, -apple-system, BlinkMacSystemFont, Arial, sans-serif',
            wordBreak: 'break-word',
            borderRadius: 2,
            textAlign: 'center',
            backgroundColor: this.merchantThemeColor,
            color: this.state.textColor,
            boxShadow: '0 0 24px 0 rgba(0,0,0,0.2)',
            zIndex: 2,
          }}
          target="_blank"
          rel="noreferrer noopener"
        >
          {this.state.textColor && btnLabel}
        </a>
        <div style={{ marginTop: 4, textAlign: 'center' }}>
          <img height="16px" src="https://cdn.razorpay.com/static/assets/powered_by_razorpay.png" />
        </div>
      </span>
    );

    return <div id="embed-btn-preview">{previewBtnCode}</div>;
  }
}
