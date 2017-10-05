import React, { Component } from 'react';
import { adminFetch } from 'util/fetch';
import Form from 'ui/Form';

export default class Table extends Component {
  state = {
    items: this.props.items || [],
    pending: false,
  };

  fetch = ({ route, queryParams } = this.props) => {
    this.setState({
      pending: true,
    });
    this.props
      .fetch({
        route,
        queryParams,
      })
      .then(({ data }) => {
        this.setState({
          items: data.data.items,
        });
      })
      .catch(e => {
        console.log(e);
      })
      .then(() => {
        this.setState({
          pending: false,
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
    let { fetch, fields, route, queryParams, model, ...props } = this.props;
    let { items, pending } = this.state;

    return (
      <div {...props}>
        {(pending && <div class="table-pending" />) ||
          ((items.length && (
            <div class="box">
              <div class="table table-striped">
                <div class="tr thead">
                  {fields.map((field, index) => (
                    <div class="th" key={index}>
                      {field[0]}
                    </div>
                  ))}
                </div>
                {items.map((item, index) => {
                  return (
                    <div class="tr" key={index}>
                      {fields.map((field, index) => (
                        <div class="td" key={index}>
                          {field[1](item)}
                        </div>
                      ))}
                    </div>
                  );
                })}
              </div>
            </div>
          )) || <div class="table-empty" />)}
      </div>
    );
  }
}

export function AdminTable(props) {
  return <Table fetch={adminFetch} {...props} />;
}
