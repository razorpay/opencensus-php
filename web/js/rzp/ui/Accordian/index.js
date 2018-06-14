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

  componentWillMount() {
    const { children, expandedKey = 0 } = this.props;
    let items = [];

    // map children props to state item
    Children.map(children, (child, index) => {
      items[index] = false;
    });

    items[expandedKey] = true;

    this.setState({ items });
  }

  render() {
    const { children } = this.props;

    return (
      <div class="Accordian">
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
  onClick = () => {},
}) => {
  const title = children[0];
  const content = children[1];
  if (expanded) {
    classNames += 'open';
  }

  return (
    <div class={`Accordian__item ${classNames}`}>
      {cloneElement(title, { onClick, uuid })}
      {content}
    </div>
  );
};

export const AccordianItemTitle = ({
  classNames = '',
  children,
  onClick,
  uuid,
}) => {
  return (
    <div
      class={`Accordian__title ${classNames}`}
      onClick={onClick}
      data-uuid={uuid}
    >
      {children}
      <div class="accordian__arrow" />
    </div>
  );
};
export const AccordianItemContent = ({ classNames = '', children }) => {
  return <div class={`Accordian__content ${classNames}`}>{children}</div>;
};
