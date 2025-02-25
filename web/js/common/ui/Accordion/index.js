import React, { Children, Component, cloneElement } from 'react';
import { classList } from 'common/utils/rzp-utils';

export default class Accordion extends Component {
  state = {
    items: {},
  };

  handleClick = (e) => {
    let items = { ...this.state.items };
    const key = e.target.dataset.uuid;

    Object.keys(items).forEach((currKey) => {
      if (currKey !== key) items[currKey] = false;
    });

    items[key] = !items[key];

    this.setState({ items });
  };

  setExpandedTab(props = this.props) {
    const { children, expandedKey = 0 } = props;
    let items = [];

    // map children props to state item
    Children.map(children, (child, index) => {
      items[index] = false;
    });

    items[expandedKey] = true;

    this.setState({ items });
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    if (this.props.expandedKey !== nextProps.expandedKey) {
      this.setExpandedTab(nextProps);
    }
  }

  UNSAFE_componentWillMount() {
    this.setExpandedTab();
  }

  render() {
    const { children, className } = this.props;

    return (
      <div className={`Accordion ${className}`}>
        {Children.map(children, (child, index) =>
          cloneElement(child, {
            uuid: index,
            onClick: this.handleClick,
            expanded: this.state.items[index],
          }),
        )}
      </div>
    );
  }
}

export const AccordionItem = ({
  className = '',
  children,
  expanded = false,
  uuid,
  status,
  cantBeOpened,
  onClick = () => {},
}) => {
  const title = children[0];
  const content = children[1];
  if (expanded) {
    className += 'open';
  }

  return (
    <div className={classList('Accordion__item', className, status)}>
      {cloneElement(title, { onClick, uuid, cantBeOpened })}
      {cloneElement(content, { cantBeOpened })}
    </div>
  );
};

export const AccordionItemTitle = ({ className = '', children, onClick, cantBeOpened, uuid }) => {
  const props = {
    className: `Accordion__title ${className}`,
    'data-uuid': uuid,
  };

  if (!cantBeOpened) {
    props.onClick = onClick;
  }

  return (
    <div {...props}>
      {children}
      {!cantBeOpened && <div className="accordion__arrow" data-uuid={props['data-uuid']} />}
    </div>
  );
};
export const AccordionItemContent = ({ className = '', cantBeOpened, children }) => {
  return cantBeOpened ? null : <div className={`Accordion__content ${className}`}>{children}</div>;
};
