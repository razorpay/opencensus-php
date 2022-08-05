import React, { Component } from 'react';
import MoreOptionsButton from 'merchant/components/Home/MoreOptionsButton';

import { trackOverflowDDClick, trackExportCSV, trackDownloadImage } from './ga';

class MoreOptionsButtonContainer extends Component {
  constructor(props) {
    super(props);

    this.handleClick = this.handleClick.bind(this);
    this.handleCSVDownload = this.handleCSVDownload.bind(this);
    this.handleImageDownload = this.handleImageDownload.bind(this);
  }

  handleClick() {
    trackOverflowDDClick(this.props.sectionTitle, this.props.tabName);
    this.props.handleClick && this.props.handleClick();
  }

  handleCSVDownload() {
    const { sectionTitle, tabName, handleCSVDownload: handleCSVDownloadProp } = this.props;
    trackExportCSV(sectionTitle, tabName);
    handleCSVDownloadProp?.();
  }

  handleImageDownload(e) {
    trackDownloadImage(this.props.sectionTitle, this.props.tabName);

    return this.props.handleImageDownload && this.props.handleImageDownload(e);
  }

  render() {
    const { sectionTitle, tabName, ...props } = this.props;

    return (
      <MoreOptionsButton
        {...props}
        handleClick={this.handleClick}
        handleCSVDownload={this.handleCSVDownload}
        handleImageDownload={this.handleImageDownload}
      />
    );
  }
}

export default MoreOptionsButtonContainer;
