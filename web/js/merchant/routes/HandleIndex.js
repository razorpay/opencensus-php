export default class HandleIndex extends React.Component {
  UNSAFE_componentWillMount() {
    if (this.props.location.hash) {
      const location = this.props.location;
      const path = location.hash.replace(/#\/?app\/?/, '') || 'dashboard';

      const newRoute = location.pathname + path;
      this.props.history.push(newRoute);
    } else {
      this.props.history.push('/dashboard');
    }
  }

  render() {
    return null;
  }
}
