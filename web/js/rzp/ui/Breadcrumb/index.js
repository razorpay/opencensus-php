import React, { Component } from 'react';

import { checkChildrenType } from 'rzp/utils/rzp-react-utils';

import './styles.styl';

class BreadcrumbItem extends Component {
  constructor(props) {
    super(props);
  }

  render() {
    const { children, ...otherProps } = this.props;

    return <li {...otherProps}>{children}</li>;
  }
}

class Breadcrumb extends Component {
  constructor(props) {
    super(props);
  }

  render() {
    const { children, className, ...otherProps } = this.props,
      numChildren = React.Children.count(children);

    if (numChildren === 0) {
      return null;
    }

    otherProps.className =
      `${className ? className : ''} breadcrumb` + ` rzp-breadcrumb`;

    return (
      <ol {...otherProps}>
        {React.Children.map(children, (child, index) => {
          const isLastChild = index + 1 === numChildren,
            { className, children, ...otherProps } = child.props,
            classNames = [className || '', isLastChild ? 'active' : ''];

          otherProps.className = classNames.join(' ');

          return <child.type {...otherProps}>{children}</child.type>;
        })}
      </ol>
    );
  }
}

Breadcrumb.propTypes = {
  children: props => {
    const { children } = props;

    return checkChildrenType(children, [BreadcrumbItem]);
  },
};

export { BreadcrumbItem };

export default Breadcrumb;
