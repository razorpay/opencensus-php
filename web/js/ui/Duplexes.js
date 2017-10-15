import React, { Component } from 'react';
import { observer } from 'mobx-react';
import TransitionGroup from 'react-transition-group/TransitionGroup';
import CSSTransition from 'react-transition-group/CSSTransition';

const animObj = {
  enter: 1000,
  exit: 700,
};

@observer
export default class Duplexes extends Component {
  render() {
    let { fields, model } = this.props;
    let { pending, items } = model;

    pending = pending.fetch;

    return (
      <div>
        {(pending && <div class="table-pending" />) ||
          (items.length && (
            <div class="table table-striped">
              {(model.props.id && (
                <TransitionGroup>
                  {items.map(m => (
                    <CSSTransition
                      key={m.props.id || 'last'}
                      classNames="row"
                      timeout={animObj}
                    >
                      <Row fields={fields} item={m} />
                    </CSSTransition>
                  ))}
                </TransitionGroup>
              )) ||
                items.map((m, i) => <Row key={i} fields={fields} item={m} />)}
            </div>
          )) || <div class="table-empty" />}
      </div>
    );
  }
}

const Row = props => (
  <div class="tr">
    {props.fields.map((fieldFn, index) => {
      var result = fieldFn(props.item);
      return (
        result && (
          <div class="td" key={index}>
            <div>{result[0]}</div>
            <div>{result[1]}</div>
          </div>
        )
      );
    })}
  </div>
);
