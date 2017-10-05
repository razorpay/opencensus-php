import React, { Component } from 'react';
import { adminFetch } from 'util/fetch';
import Form from 'ui/Form';

export default class Table extends Component {
  state = {
    items: this.props.items,
  };

  fetch = ({ route, query_params } = this.props) => {
    this.props
      .fetch({
        route,
        query_params,
      })
      .then(({ data }) => {
        this.setState({
          items: data,
        });
      });
  };

  componentWillMount() {
    this.fetch();
  }

  componentWillReceiveProps(props) {
    this.fetch(props);
  }

  render() {
    let { items } = this.state;

    return (
      <div>
        {(items &&
          ((items.length &&
            makeTable(this.props.fields, items, this.props.form)) || (
              <div class="">No Items</div>
            ))) || <div class="loading" />}
        {this.props.children &&
          items &&
          items.length &&
          React.Children.map(this.props.children, child =>
            React.cloneElement(child, { items })
          )}
      </div>
    );
  }
}

function makeTable(fields, items, formProps) {
  return (
    <div class="table pure-table pure-table-bordered pure-table-striped">
      <div class="tr thead">
        {fields.map((field, index) => {
          return (
            <div class="th" key={index}>
              {field[0]}
            </div>
          );
        })}
      </div>
      {items.map((item, index) => {
        return (
          <Form {...formProps} class="tr pure-form" key={index}>
            {fields.map((field, index) => {
              return (
                <div class="td" key={index}>
                  {field[1](item)}
                </div>
              );
            })}
          </Form>
        );
      })}
    </div>
  );
}

export function AdminTable(props) {
  return <Table fetch={adminFetch} {...props} />;
}
