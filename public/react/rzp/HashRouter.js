import { Router } from 'react-router-dom';
import { HashRouter } from 'react-router-dom';
import createHashHistory from 'history/createHashHistory';

const history = createHashHistory();
let shouldNotify = true;
const push = history.push;

history.push = function(path, options = {}, state) {
  if (options.notify === false) {
    shouldNotify = false;
  } else {
    shouldNotify = true;
  }
  push(path, state);
};

class RouterExt extends Router {
  componentWillMount() {
    // Call super so that we don't miss any functionalities added to the parent
    super.componentWillMount();

    // Unregister the previous listener
    this.unlisten();

    // Register the custom listener
    this.unlisten = this.props.history.listen(() => {
      if (shouldNotify) {
        this.setState({
          match: this.computeMatch(history.location.pathname),
        });
      } else {
        shouldNotify = true;
      }
    });
  }
}

export default class HashRouterExt extends HashRouter {
  history = history;

  render() {
    return <RouterExt history={this.history} children={this.props.children} />;
  }
}
