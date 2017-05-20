/*
 * React Router doesn't support changing URL without rerendering the component
 * https://github.com/ReactTraining/react-router/issues/266
 *
 * We've few use cases where we want to change the url without rerendering the component like
 *    - When showing txn details in Slider view, we need to update the url to /entity/:id
 *    - During signup flow, when transitioning between various signup steps keeping the URL intact
 *
 * Currently, we do this with angular router like ```$state.transitionTo(route, { notify: false })```
 *
 * USAGE:
 *    This change allows you to specify the `notify` option while calling `history.push`. Doing so,
 * will just upadate the URL without rerendering the component.
 *
 * ```this.props.history.push(route, { notify: false });```
 */

import { Router } from 'react-router-dom';
import { HashRouter } from 'react-router-dom';
import createHashHistory from 'history/createHashHistory';

const history = createHashHistory();
const push = history.push;
let shouldNotify = true;

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
