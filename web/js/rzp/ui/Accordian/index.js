import { Children, Component, cloneElement } from 'react';

export default class Accordian extends Component {
  state = {
    items: {},
  };

  handleClick = e => {
    let items = { ...this.state.items };
    const key = e.target.dataset.uuid;

    Object.keys(items).forEach(currKey => {
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

  componentWillReceiveProps(nextProps) {
    if (this.props.expandedKey !== nextProps.expandedKey) {
      this.setExpandedTab(nextProps);
    }
  }

  componentWillMount() {
    this.setExpandedTab();
  }

  render() {
    const { children, classNames } = this.props;

    return (
      <div class={`Accordian ${classNames}`}>
        {Children.map(children, (child, index) =>
          cloneElement(child, {
            uuid: index,
            onClick: this.handleClick,
            expanded: this.state.items[index],
          })
        )}
      </div>
    );
  }
}

export const AccordianItem = ({
  classNames = '',
  children,
  expanded = false,
  uuid,
  cantBeOpened,
  onClick = () => {},
}) => {
  const title = children[0];
  const content = children[1];
  if (expanded) {
    classNames += 'open';
  }

  return (
    <div class={`Accordian__item ${classNames}`}>
      {cloneElement(title, { onClick, uuid, cantBeOpened })}
      {cloneElement(content, { cantBeOpened })}
    </div>
  );
};

export const AccordianItemTitle = ({
  classNames = '',
  children,
  onClick,
  cantBeOpened,
  uuid,
}) => {
  const props = {
    className: `Accordian__title ${classNames}`,
    'data-uuid': uuid,
  };

  if (!cantBeOpened) {
    props.onClick = onClick;
  }

  return (
    <div {...props}>
      {children}
      {!cantBeOpened && <div class="accordian__arrow" />}
    </div>
  );
};
export const AccordianItemContent = ({
  classNames = '',
  cantBeOpened,
  children,
}) => {
  return cantBeOpened ? null : (
    <div class={`Accordian__content ${classNames}`}>{children}</div>
  );
};
