import { Component } from 'react';

import SupportHeader from 'merchant/components/Support/SupportHeader';
import SupportBody from 'merchant/components/Support/SupportBody';

import { classList } from 'common/util';

export default class Support extends Component {
  state = {
    isOpened: false,
    isHidden: false,
    notifyCount: 0,
  };

  componentDidMount() {
    this.bindEvents();

    // hide smooch's iframe messenger button when a notifcation arrives
    document
      .getElementById('web-messenger-container')
      .contentWindow.document.getElementById('messenger-button').style.display =
      'none';
  }

  bindEvents = () => {
    //bind events for freshchat if available
    if (window.fcWidget) {
      window.fcWidget.on('widget:opened', () => {
        this.handleVisibility(true);
      });
      window.fcWidget.on('widget:closed', () => {
        this.handleVisibility(false);
      });

      window.fcWidget.on('unreadCount:notify', response => {
        this.setState({ notifyCount: response.count });
      });
    }

    //bind events for smooch if available
    if (window.Smooch) {
      window.Smooch.on('widget:opened', () => {
        this.handleVisibility(true);
      });
      window.Smooch.on('widget:closed', () => {
        // smooch doesn't update notification count when chat is opened
        this.setState({ notifyCount: 0 });

        this.handleVisibility(false);
      });
      window.Smooch.on('unreadCount', unreadCount => {
        this.setState({ notifyCount: unreadCount });
      });
    }
  };

  handleToggle = () => {
    const { isOpened } = this.state;
    this.setState({
      isOpened: !isOpened,
    });
  };

  handleVisibility = shouldHide => {
    this.setState({ isHidden: shouldHide });
  };

  handleChat = () => {
    if (window.Smooch) {
      window.Smooch.open();
    }

    if (window.fcWidget) {
      window.fcWidget.open();
    }

    this.handleVisibility(true);
  };

  render() {
    const { notifyCount, isOpened, isHidden } = this.state;

    return (
      <div class={classList('support', isHidden && 'hidden')}>
        <SupportHeader
          onToggle={this.handleToggle}
          isOpened={isOpened}
          notifyCount={notifyCount}
        />
        <SupportBody
          onToggle={this.handleToggle}
          isOpened={isOpened}
          onChat={this.handleChat}
          notifyCount={notifyCount}
        />
      </div>
    );
  }
}
