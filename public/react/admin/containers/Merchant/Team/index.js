import { Component } from 'react';
import { connect } from 'react-redux';
import Header from 'rzp/ui/Header';
import { fetchTeamDetails } from 'rzp/modules/team';
import DataTable from 'rzp/ui/Table/DataTable';
import { name, email, role } from 'admin/pairs';

@connect(state => state.team, { fetchTeamDetails })
export default class MerchantTeam extends Component {
  componentWillMount() {
    this.props.fetchTeamDetails({
      merchant_id: this.props.id,
    });
  }

  render() {
    let { invitations, users, loading } = this.props;
    return (
      <div>
        <Header title={`Merchant ${this.props.id} - Team Details`} />
        <div class="content-wrapper">
          <div class="panel panel-default">
            <div class="panel-heading">
              Users
            </div>
            <DataTable
              title="Users"
              items={users}
              loading={loading}
              columns={[name, email, role]}
            />
          </div>

          <div class="panel panel-default">
            <div class="panel-heading">
              Pending Invitations
            </div>
            <DataTable
              title="Invitations"
              items={invitations}
              loading={loading}
              columns={[email, role]}
            />
          </div>
        </div>
      </div>
    );
  }
}
