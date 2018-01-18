import React, { Component } from 'react';
import html2canvas from 'html2canvas';

class Screenshot extends Component {
  constructor(props) {
    super(props);
  }

  takeScreenshot() {
    this.resolve = url => {
      return this.props.onScreenshot(url);
    };

    return (resolve => {
      return html2canvas(this.node).then(canvas => {
        if (this.resolve !== resolve) {
          return;
        }

        this.resolve(canvas.toDataURL());
      });
    })(this.resolve);
  }

  componentDidMount() {
    debugger;
    //this.takeScreenshot();
  }

  componentDidUpdate(prevProps, prevState) {
    debugger;
    //this.takeScreenshot();
  }

  render() {
    return (
      <div ref={node => (this.node = node)} className="rzp-screenshot">
        {this.props.children}
      </div>
    );
  }
}

export default Screenshot;
