const joinClass = (props, className) => props.className ? className + ' ' + props.className : className;

export default class Button extends React.PureComponent {
  render() {
    let {
      iconBefore,
      iconAfter,
      children,
      ...props
    } = this.props;

    return <button {...props} class={joinClass(props, 'Button')}>
      {iconBefore && <i class={'Button-icon Button-icon--before i-' + iconBefore}/>}
      {children}
      {iconAfter && <i class={'Button-icon Button-icon--after i-' + iconAfter}/>}
    </button>
  }
}

Button.Primary = props => <Button {...props} class={joinClass(props, 'Button--primary')} />
