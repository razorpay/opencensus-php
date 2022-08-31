import React, { Component } from 'react';
import { updateExtension } from 'common/utils/rzp-utils';
/*
 * Image replacement which will show placeholder component
 * if url is not passed or if the image can not be loaded
 *
 * You can pass the placeholder through children
 * class `invalid-src` will be added to the element if
 * we are not able to fetch the image
 */
class Image extends Component {
  constructor(props) {
    super(props);
    this.state = {
      loading: !props.src,
      validUrl: !!props.src,
    };

    this.onImageFetchSuccess = this.onImageFetchSuccess.bind(this);
    this.onImageFetchError = this.onImageFetchError.bind(this);
  }

  onImageFetchSuccess() {
    this.setState({
      loading: false,
      validUrl: true,
    });
  }

  onImageFetchError() {
    this.setState({
      loading: false,
      validUrl: false,
    });
  }

  render() {
    const { children, src, isWebP = false } = this.props;
    const { validUrl, loading } = this.state;
    return (
      <div className={`rzp-image${!validUrl ? ' invalid-src' : ''}`}>
        {loading || !validUrl ? (
          children
        ) : (
          <picture>
            {isWebP && <source srcSet={updateExtension(src, '.webp')} type="image/webp" />}
            <img
              srcSet={src}
              src={src}
              onLoad={this.onImageFetchSuccess}
              onError={this.onImageFetchError}
            />
          </picture>
        )}
      </div>
    );
  }
}

export default Image;
