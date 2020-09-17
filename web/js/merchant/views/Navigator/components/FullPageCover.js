import React, { Component } from 'react';
import PropTypes from 'prop-types';

export default class FullPageCover extends Component {
  constructor(props) {
    super(props);
  }

  render() {
    return <div class="cover-full-page-container">{this.props.children}</div>;
  }
}
