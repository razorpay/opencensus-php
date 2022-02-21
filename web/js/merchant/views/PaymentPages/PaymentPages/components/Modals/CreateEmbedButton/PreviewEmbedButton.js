import { connect } from 'react-redux';
import React from 'react';

@connect(
  (state) => ({
    config: state.config.config,
  }),
  null,
)
export default class PreviewEmbedButton extends React.Component {
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
    const { url, btnSize = '0', btnLabel = 'Pay Now', showPreviewLabel } = this.props;

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
      <span style={{ position: 'relative' }}>
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

    return (
      <div id="embed-btn-preview">
        {!!showPreviewLabel && <span className="preview-label">Preview</span>}
        {previewBtnCode}
      </div>
    );
  }
}
