import fetch from 'util/fetch';

export default class Model {
  pending = {};
  forceUpdate = _ => _;

  constructor(props) {
    if (typeof props === 'string') {
      props = {
        id: props,
      };
    }
    this.props = props;
  }

  request(name, promise) {
    this.pending[name] = promise;
    this.forceUpdate();
    return promise
      .then(response => {
        return response.json().then(({ data }) => {
          this.props = data;
        });
      })
      .catch(_ => _)
      .then(_ => {
        delete this.pending[name];
        this.forceUpdate();
      });
  }

  get(url) {
    return fetch('/user/generic', {
      body: {
        route_name: url,
        url_params: `{"{id}":"${this.props.id}"}`,
      },
    });
  }
}

export function withModel(model, asName = 'model') {
  let returnFunc = Component => props =>
    React.createElement(Component, { [asName]: model });
  returnFunc.as = name => withModel(model, asName);
  return returnFunc;
}
