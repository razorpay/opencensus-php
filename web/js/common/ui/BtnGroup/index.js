import React, { Component } from 'react';
import PropTypes from 'prop-types';
import { checkChildrenType } from 'common/utils/react-utils';

export class Btn extends Component {
  constructor(props) {
    super(props);

    this.handleClick = this.handleClick.bind(this);
  }

  handleClick() {
    this.props.onBtnClick(this.props.value);
  }

  render() {
    const {
      value,
      className = '',
      active,
      onBtnClick,
      // we will be discarding onClick if any specified
      onClick,
      selected,
      children,
      ...otherProps
    } = this.props;

    otherProps.className = `${className} btn${selected === value ? ' active' : ''}`;

    return (
      <button {...otherProps} onClick={this.handleClick}>
        {children}
      </button>
    );
  }
}

Btn.propTypes = {
  value: PropTypes.any.isRequired,
};

export class BtnGroup extends Component {
  constructor(props) {
    super(props);

    this.state = {
      value: props.value || null,
    };

    this.handleBtnClick = this.handleBtnClick.bind(this);
  }

  handleBtnClick(value) {
    this.setState({ value });
    return this.props.onChange && this.props.onChange(value);
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    if (this.state.value !== nextProps.value) {
      this.setState({ value: nextProps.value });
    }
  }

  render() {
    const { className = '', value, onChange, children, ...otherProps } = this.props;

    otherProps.className = `${className} rzp-btn-group btn-group`;

    const boundChildren = React.Children.map(children, (child) => {
      return React.cloneElement(child, {
        onBtnClick: this.handleBtnClick,
        selected: this.state.value,
      });
    });

    return <div {...otherProps}>{boundChildren}</div>;
  }
}

BtnGroup.propTypes = {
  children: (props) => {
    const { children } = props;

    return checkChildrenType(children, [Btn]);
  },
};
