import React from 'react';
import { connect } from 'react-redux';
import { Navigate } from 'react-router-dom';
import { withRouter } from 'common/deprecated/withRouter';
import ListContainer from 'merchant/containers/ListContainer';
import RouteSettlementListFilter from './components/RouteSettlementListFilter';
import RouteSettlementsList from './components/RouteSettlementsList';
import { fetchRouteOndemandSettlements as fetchAll } from 'merchant/reducers/collection';
import Alert from 'common/ui/Forms/Alert';
import Pager from 'common/ui/Pager';

class RouteOndemandSettlements extends ListContainer {
  state = {
    count: 25,
    skip: 0,
  };

  render() {
    const { user, loading, items = [], error } = this.props;

    if (!user.isOndemandSettlementEnabled) return <Navigate to="/settlements" replace />;

    return (
      <content>
        <div className="content-wrapper">
          <RouteSettlementListFilter
            form="instantRouteSettlementListFilter"
            count={this.state.count}
            onSubmit={this.search}
          />
          {error && <Alert type="error" message={error} />}
          <RouteSettlementsList settlements={items} isLoading={loading} />

          <Pager
            count={this.state.count}
            length={items.length}
            skip={this.state.skip}
            onClick={this.paginate}
          />
        </div>
      </content>
    );
  }
}

const mapStateToProps = (state) => ({
  user: state.session.user,
  ...state.routeOndemandSettlements,
});

const mapDispatchToProps = {
  fetchAll,
};

export default withRouter(connect(mapStateToProps, mapDispatchToProps)(RouteOndemandSettlements));
