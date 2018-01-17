import React, { Component } from 'react';

import { checkChildrenType } from 'rzp/utils/rzp-react-utils';

import './styles.styl';

class GroupItem extends Component {
  constructor(props) {
    super(props);
  }

  render() {
    const { children, className } = this.props;

    return (
      <div className={`rzp-group-item${className ? ' ' + className : ''}`}>
        {this.props.children}
      </div>
    );
  }
}

class Group extends Component {
  constructor(props) {
    super(props);
  }

  render() {
    return <div className="rzp-group">{this.props.children}</div>;
  }
}

Group.propTypes = {
  children: props => checkChildrenType(props.children, GroupItem),
};

export { GroupItem };

export default Group;
