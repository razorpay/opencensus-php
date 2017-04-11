import React, { Component, PropTypes } from 'react'
import { connect } from 'react-redux'
import { refreshConfig } from 'merchant/modules/config'
import * as NotificationActions from 'merchant/modules/notifications'

@connect(
  (state) => {
    return {
      config: state.config.config
    }
  },
  { refreshConfig, ...NotificationActions }
)
export default class Congfiguration extends Component {

  render() {
    return (
      <div class="panel panel-default">
        <div class="panel-heading">
          Email Notifications
        </div>
        <div class="panel-body">

          <div class="form-group">
            <div class="">
              <div class="help-block m-b">
                Enter email addresses that will receive email notifications regarding payments, settlements, daily payment reports, webhooks, etc. (You can enter multiple email addresses separated by a comma.)
              </div>
            </div>
          </div>
          <div class="m-t m-b text-center prev-next">
            <div class="col-sm-10">
              <input type="text"
                     class="form-control m-l-neg"
                     maxLength="255"
                     value ={this.props.config.transaction_report_email ? this.props.config.transaction_report_email : ''}
                     onChange={(evnt)=>{
                       let {transaction_report_email , ...rest} = this.props.config;
                       transaction_report_email = evnt.target.value;
                       let newConfig = {transaction_report_email, ...rest}
                       this.props.refreshConfig(newConfig);
                     }}/>
            </div>
            <button class="btn btn-save btn-default btn-rounded col-sm-2"
                    type="submit"
                    onClick={this.props.saveConfig}>
              Save Changes
            </button>
          </div>
        </div>
        <footer class="panel-footer">
          {/*
           <div class="text-center m-t m-b">
           <alert ng-repeat="alert in alerts.getAlerts()" type="{{alert.type}}" close="alerts.closeAlert($index)">{{alert.msg}}</alert>
           </div>
           */}
        </footer>
      </div>
    );
  }
}
