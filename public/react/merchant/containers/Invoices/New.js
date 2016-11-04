import React, { Component } from 'react'
import Header from 'rzp/ui/Header'

export default class InvoicesNewContainer extends Component {
  render() {
    return (
      <div>
        <Header title='New Invoice'>
          <a href='#/app/invoices' className='pull-right btn btn-link btn-sm'>
            <i className='fa fa-close'></i>
          </a>
        </Header>

        <div className='content-wrapper'>
          <div className='panel panel-default'>
            <div className='panel-body'>
              <h3>Invoice form goes here</h3>
            </div>
          </div>
        </div>
      </div>
    )
  }
}
