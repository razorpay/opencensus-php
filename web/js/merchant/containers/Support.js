import { Component } from 'react';

import SupportHeader from 'merchant/components/Support/SupportHeader';

export default class Support extends Component {
  state = {
    isOpened: false,
  };

  handleToggle = () => {
    const { isOpened } = this.state;
    this.setState({
      isOpened: !isOpened,
    });
  };

  render() {
    return (
      <div>
        <SupportHeader
          isOpen={this.state.isOpened}
          onToggle={this.handleToggle}
        />
      </div>
    );
  }
}
