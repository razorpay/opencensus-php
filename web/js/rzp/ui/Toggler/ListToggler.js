import { Component } from 'react';
import { NavLink } from 'react-router-dom';

/*
 // Usage: Check PaymentDetails
 // Constraints: Don't pass props `limitUrl` if `limit` is not passed as it won't be utlized
 // Eg: Refund list
*/

export default class ListToggler extends Component {
  render() {
    let {
      loading,
      limit,
      totalItems,
      label,
      subLabel,
      limitUrl,
      onViewAllClick = () => {},
    } = this.props;

    return (
      <div class="list-table">
        <span class="list-label">
          <b>{label}</b> {subLabel}
        </span>
        {!loading && limit && limit < totalItems && <span> • </span>}

        <span class="primary-link">
          {!loading &&
            limit &&
            limit < totalItems && (
              <NavLink to={limitUrl} onClick={onViewAllClick}>
                View all <b>{totalItems} &gt;</b>
              </NavLink>
            )}
        </span>

        <div class="panel-body" style={{ padding: '15px 0' }}>
          <div class="list-group detail-row-container">
            {this.props.children}
          </div>
        </div>
      </div>
    );
  }
}
