import React, { Component } from 'react';
import PropTypes from 'prop-types';

export default class FullPageCoverHeader extends Component {
  constructor(props) {
    super(props);
  }

  render() {
    return <div class="cover-full-page-header">{this.props.children}</div>;
  }
}
